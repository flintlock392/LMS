<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table         = 'categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'description'];
    protected $useTimestamps = true;

    /** All categories with the number of books and total copies in each. */
    public function withBookCounts(): array
    {
        return $this->select('categories.*, COUNT(books.id) AS book_count, COALESCE(SUM(books.stock_count), 0) AS copies')
            ->join('books', 'books.category_id = categories.id', 'left')
            ->groupBy('categories.id')
            ->orderBy('categories.name', 'ASC')
            ->findAll();
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $builder = $this->where('name', $name);
        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }
}
