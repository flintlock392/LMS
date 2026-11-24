<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryLogModel extends Model
{
    /** Reasons that add copies and reasons that remove them. */
    public const ADD_REASONS    = ['acquired' => 'New acquisition', 'donated' => 'Donation', 'found' => 'Found / recovered'];
    public const REMOVE_REASONS = ['damaged' => 'Damaged beyond use', 'lost' => 'Lost', 'removed' => 'Weeded / removed'];
    public const LABELS = self::ADD_REASONS + self::REMOVE_REASONS + ['initial' => 'Initial stock', 'correction' => 'Count correction'];

    protected $table         = 'inventory_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['book_id', 'change', 'reason', 'stock_after', 'notes', 'user_id'];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    public function recent(int $limit = 50, ?int $bookId = null): array
    {
        $this->select('inventory_logs.*, books.title, books.isbn, users.name AS user_name')
            ->join('books', 'books.id = inventory_logs.book_id')
            ->join('users', 'users.id = inventory_logs.user_id', 'left')
            ->orderBy('inventory_logs.id', 'DESC');

        if ($bookId !== null) {
            $this->where('inventory_logs.book_id', $bookId);
        }

        return $this->findAll($limit);
    }
}
