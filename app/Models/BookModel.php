<?php

namespace App\Models;

use CodeIgniter\Model;

class BookModel extends Model
{
    /** Books with this many copies or fewer (but more than 0) count as "low stock". */
    public const LOW_STOCK = 5;

    public const SORTABLE = ['title', 'author', 'isbn', 'stock_count', 'publication_year', 'created_at'];

    protected $table         = 'books';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'title', 'isbn', 'author', 'category_id', 'publisher',
        'publication_year', 'stock_count', 'description',
    ];
    protected $useTimestamps = true;

    /**
     * Apply the list filters and return the model so the caller can paginate().
     *
     * Supported keys: q, category_id, author, availability, sort, dir
     */
    public function filtered(array $f): self
    {
        $this->select('books.*, categories.name AS category_name')
            ->join('categories', 'categories.id = books.category_id', 'left');

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

        switch ($f['availability'] ?? '') {
            case 'in_stock':
                $this->where('books.stock_count >', 0);
                break;
            case 'low_stock':
                $this->where('books.stock_count >', 0)->where('books.stock_count <=', self::LOW_STOCK);
                break;
            case 'out_of_stock':
                $this->where('books.stock_count', 0);
                break;
        }

        $sort = in_array($f['sort'] ?? '', self::SORTABLE, true) ? $f['sort'] : 'title';
        $dir  = strtolower($f['dir'] ?? '') === 'desc' ? 'DESC' : 'ASC';
        $this->orderBy('books.' . $sort, $dir);

        return $this;
    }

    /** Distinct author names for the filter dropdown. */
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

    public function findWithCategory(int $id): ?array
    {
        return $this->select('books.*, categories.name AS category_name')
            ->join('categories', 'categories.id = books.category_id', 'left')
            ->where('books.id', $id)
            ->first();
    }

    /** Numbers shown on the dashboard. */
    public function stats(): array
    {
        $row = $this->db->table($this->table)
            ->select('COUNT(*) AS titles, COALESCE(SUM(stock_count), 0) AS copies', false)
            ->get()->getRowArray();

        return [
            'titles'       => (int) $row['titles'],
            'copies'       => (int) $row['copies'],
            'out_of_stock' => $this->db->table($this->table)->where('stock_count', 0)->countAllResults(),
            'low_stock'    => $this->db->table($this->table)
                ->where('stock_count >', 0)->where('stock_count <=', self::LOW_STOCK)->countAllResults(),
        ];
    }
}
