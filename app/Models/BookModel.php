<?php

namespace App\Models;

use CodeIgniter\Model;

class BookModel extends Model
{
    /** Titles with this many available copies or fewer (but more than 0) count as "low". */
    public const LOW_STOCK = 2;

    public const SORTABLE = ['title', 'author', 'isbn', 'stock_count', 'publication_year', 'created_at', 'available'];

    /** Copies currently out on loan. */
    public const SQL_ON_LOAN = "(SELECT COUNT(*) FROM borrowings br WHERE br.book_id = books.id AND br.status = 'borrowed')";

    /** Copies held at the desk for a "ready" reservation. */
    public const SQL_ON_HOLD = "(SELECT COUNT(*) FROM reservations rs WHERE rs.book_id = books.id AND rs.status = 'ready')";

    /** Copies waiting in the reservation queue. */
    public const SQL_QUEUE = "(SELECT COUNT(*) FROM reservations rq WHERE rq.book_id = books.id AND rq.status = 'pending')";

    protected $table         = 'books';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'title', 'isbn', 'author', 'category_id', 'publisher',
        'publication_year', 'shelf_location', 'stock_count', 'description',
    ];
    protected $useTimestamps = true;

    public static function availableSql(): string
    {
        return '(books.stock_count - ' . self::SQL_ON_LOAN . ' - ' . self::SQL_ON_HOLD . ')';
    }

    /** Select books with their category name and live circulation counts. */
    public function withAvailability(): self
    {
        $this->select('books.*, categories.name AS category_name', false)
            ->select(self::SQL_ON_LOAN . ' AS on_loan', false)
            ->select(self::SQL_ON_HOLD . ' AS on_hold', false)
            ->select(self::SQL_QUEUE . ' AS queued', false)
            ->select(self::availableSql() . ' AS available', false)
            ->join('categories', 'categories.id = books.category_id', 'left');

        return $this;
    }

    /**
     * Apply the list filters and return the model so the caller can paginate().
     *
     * Supported keys: q, category_id, author, availability, sort, dir
     */
    public function filtered(array $f): self
    {
        $this->withAvailability();

        if (! empty($f['q'])) {
            $term = $f['q'];
            $this->groupStart()
                ->like('books.title', $term)
                ->orLike('books.author', $term)
                ->orLike('books.isbn', str_replace('-', '', $term))
                ->orLike('books.publisher', $term)
                ->groupEnd();
        }

        if (! empty($f['category_id'])) {
            if ($f['category_id'] === 'none') {
                $this->where('books.category_id', null);
            } else {
                $this->where('books.category_id', (int) $f['category_id']);
            }
        }

        if (! empty($f['author'])) {
            $this->where('books.author', $f['author']);
        }
        if (! empty($f['added_from'])) {
            $this->where('books.created_at >=', $f['added_from'] . ' 00:00:00');
        }
        if (! empty($f['added_to'])) {
            $this->where('books.created_at <=', $f['added_to'] . ' 23:59:59');
        }

        $available = self::availableSql();
        switch ($f['availability'] ?? '') {
            case 'in_stock':
                $this->where($available . ' > 0', null, false);
                break;
            case 'low_stock':
                $this->where($available . ' > 0', null, false)->where($available . ' <= ' . self::LOW_STOCK, null, false);
                break;
            case 'out_of_stock':
                $this->where($available . ' <= 0', null, false);
                break;
        }

        $sort = in_array($f['sort'] ?? '', self::SORTABLE, true) ? $f['sort'] : 'title';
        $dir  = strtolower($f['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';
        $this->orderBy($sort === 'available' ? 'available' : 'books.' . $sort, $dir, false);

        return $this;
    }

    public function findWithAvailability(int $id): ?array
    {
        return $this->withAvailability()->where('books.id', $id)->first();
    }

    /** Copies that can be lent right now (excludes loans and desk holds). */
    public function availableCopies(int $id): int
    {
        $row = $this->db->query('SELECT ' . self::availableSql() . ' AS available FROM books WHERE books.id = ?', [$id])->getRowArray();

        return (int) ($row['available'] ?? 0);
    }

    public function distinctAuthors(): array
    {
        return array_column(
            $this->db->table($this->table)->select('author')->distinct()->orderBy('author', 'ASC')->get()->getResultArray(),
            'author'
        );
    }

    public function isbnExists(string $isbn, ?int $ignoreId = null): bool
    {
        $builder = $this->db->table($this->table)->where('isbn', $isbn);
        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }

    /** Totals shown on dashboards and inventory screens. */
    public function stats(): array
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS titles, COALESCE(SUM(stock_count), 0) AS copies,
                    COALESCE(SUM(' . self::SQL_ON_LOAN . '), 0) AS on_loan,
                    COALESCE(SUM(' . self::SQL_ON_HOLD . '), 0) AS on_hold,
                    SUM(CASE WHEN ' . self::availableSql() . ' <= 0 THEN 1 ELSE 0 END) AS unavailable,
                    SUM(CASE WHEN ' . self::availableSql() . ' > 0 AND ' . self::availableSql() . ' <= ' . self::LOW_STOCK . ' THEN 1 ELSE 0 END) AS low
             FROM books'
        )->getRowArray();

        return [
            'titles'      => (int) $row['titles'],
            'copies'      => (int) $row['copies'],
            'on_loan'     => (int) $row['on_loan'],
            'on_hold'     => (int) $row['on_hold'],
            'available'   => (int) $row['copies'] - (int) $row['on_loan'] - (int) $row['on_hold'],
            'unavailable' => (int) $row['unavailable'],
            'low'         => (int) $row['low'],
        ];
    }

    /** Short list for circulation drop-downs. */
    public function forSelect(): array
    {
        return $this->select('books.id, books.title, books.isbn, books.author', false)
            ->select(self::availableSql() . ' AS available', false)
            ->orderBy('books.title', 'ASC')
            ->findAll();
    }
}
