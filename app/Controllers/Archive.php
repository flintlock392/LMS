<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\CategoryModel;
use App\Models\UserModel;

/**
 * Head Librarian: everything that was archived (books, categories, student and
 * staff accounts), with a Restore button for each.
 */
class Archive extends BaseController
{
    /** type => [table, label, activity category] */
    private const TYPES = [
        'book'     => ['books', 'Book', 'book'],
        'category' => ['categories', 'Category', 'book'],
        'student'  => ['users', 'Student account', 'user'],
        'staff'    => ['users', 'Staff account', 'staff'],
    ];

    public function index()
    {
        $users = static fn () => (new UserModel())->onlyDeleted()->orderBy('archived_at', 'DESC');

        return view('archive/index', [
            'books'      => (new BookModel())->onlyDeleted()->select('books.*')->orderBy('archived_at', 'DESC')->findAll(),
            'categories' => (new CategoryModel())->onlyDeleted()->orderBy('archived_at', 'DESC')->findAll(),
            'students'   => $users()->where('role', UserModel::STUDENT)->findAll(),
            'staff'      => $users()->whereIn('role', UserModel::STAFF_ROLES)->findAll(),
        ]);
    }

    public function restore(string $type, int $id)
    {
        if (! isset(self::TYPES[$type])) {
            return redirect()->to('/archive')->with('error', 'Unknown item type.');
        }
        [$table, $label, $logCategory] = self::TYPES[$type];

        $builder = db_connect()->table($table)->where('id', $id)->where('archived_at IS NOT NULL', null, false);
        if ($table === 'users') {
            $type === 'student' ? $builder->where('role', UserModel::STUDENT) : $builder->whereIn('role', UserModel::STAFF_ROLES);
        }
        $row = (clone $builder)->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/archive')->with('error', $label . ' not found in the archive.');
        }

        $builder->update(['archived_at' => null]);
        $name = $row['title'] ?? $row['name'];
        log_activity($logCategory, 'restored', 'Restored ' . strtolower($label) . ' "' . $name . '" from the archive', $table === 'users' ? 'user' : $type, $id);

        return redirect()->to('/archive')->with('success', $label . ' "' . $name . '" was restored.');
    }
}
