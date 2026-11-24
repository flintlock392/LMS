<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Suggestive search for the filter boxes.
 *
 *   GET suggest?scope=loans&q=jua
 *
 * Scopes (what each search box can find):
 *   loans    Library loan tabs   → students who borrowed + book titles/ISBNs
 *   books    Library Collections/Books tabs → titles, authors, ISBNs
 *   students User Management → Students   → name, student no., email, course
 *   staff    User Management → Staff (Head Librarian only) → name, Staff ID, email
 *   fines    Fine Management → students with fines + book titles on fines
 *
 * Each suggestion: {type, label, sub, value}. Choosing one puts "value"
 * in the search box and applies the filter.
 */
class Suggest extends BaseController
{
    private const LIMIT = 8;

    public function index()
    {
        $scope = (string) $this->request->getGet('scope');
        $q     = trim((string) $this->request->getGet('q'));

        if ($q === '' || mb_strlen($q) > 100) {
            return $this->response->setJSON([]);
        }
        if ($scope === 'staff' && ! is_head()) {
            return $this->response->setStatusCode(403)->setJSON([]);
        }

        $items = match ($scope) {
            'loans'    => array_merge($this->students($q, 'loans'), $this->books($q, 'loans')),
            'books'    => $this->books($q, 'all'),
            'students' => $this->students($q, 'all'),
            'staff'    => $this->staff($q),
            // Fine Management follows its "Search by" choice.
            'fines'    => match ((string) $this->request->getGet('by')) {
                'student_no' => $this->students($q, 'fines', 'student_no'),
                'name'       => $this->students($q, 'fines', 'name'),
                'book'       => $this->books($q, 'fines'),
                default      => array_merge($this->students($q, 'fines'), $this->books($q, 'fines')),
            },
            // Active students only (used by pickers such as "Add a fine").
            'borrowers' => $this->students($q, 'active'),
            default    => [],
        };

        return $this->response->setJSON(array_slice($items, 0, self::LIMIT));
    }

    /** Escaped "starts with" check so the best matches come first. */
    private function startsWith(array $columns, string $q): string
    {
        $db   = db_connect();
        $like = $db->escape($db->escapeLikeString($q) . '%');

        return '(' . implode(' OR ', array_map(static fn ($c) => $c . ' LIKE ' . $like, $columns)) . ')';
    }

    /**
     * $only:  all | loans (has borrowed) | fines (has a fine) | active (not suspended)
     * $match: any (name, number, email, course) | student_no | name
     */
    private function students(string $q, string $only, string $match = 'any'): array
    {
        $b = db_connect()->table('users')
            ->select('users.id, users.name, users.id_number, users.course, users.status')
            ->where('users.role', UserModel::STUDENT);

        if ($match === 'student_no') {
            $b->like('users.id_number', $q);
        } elseif ($match === 'name') {
            $b->like('users.name', $q);
        } else {
            $b->groupStart()->like('users.name', $q)->orLike('users.id_number', $q)->orLike('users.email', $q)->orLike('users.course', $q)->groupEnd();
        }

        if ($only === 'active') {
            $b->where('users.status', 'active');
        } elseif ($only === 'loans') {
            $b->where('EXISTS (SELECT 1 FROM borrowings x WHERE x.user_id = users.id)', null, false);
        } elseif ($only === 'fines') {
            $b->select("(SELECT COALESCE(SUM(f.amount),0) FROM fines f WHERE f.user_id = users.id AND f.status = 'unpaid') AS unpaid", false)
              ->where('EXISTS (SELECT 1 FROM fines x WHERE x.user_id = users.id)', null, false);
        }

        $rows = $b->orderBy($this->startsWith(['users.name', 'users.id_number'], $q), 'DESC', false)
            ->orderBy('users.name')->limit(self::LIMIT)->get()->getResultArray();

        return array_map(static fn ($u) => [
            'type'  => 'student',
            'id'    => (int) $u['id'],
            // Searching by number shows the number first; otherwise the name.
            'label' => $match === 'student_no' ? $u['id_number'] : $u['name'],
            'sub'   => ($match === 'student_no' ? $u['name'] : $u['id_number']) . ($u['course'] ? ' · ' . $u['course'] : '')
                . (isset($u['unpaid']) && (float) $u['unpaid'] > 0 ? ' · owes ' . money($u['unpaid']) : '')
                . ($u['status'] !== 'active' ? ' · suspended' : ''),
            'value' => $match === 'name' ? $u['name'] : $u['id_number'],
            'pick'  => $u['name'] . ' (' . $u['id_number'] . ')',
        ], $rows);
    }

    /** $only: all | loans (has been borrowed) | fines (has a fine) */
    private function books(string $q, string $only): array
    {
        $b = db_connect()->table('books')
            ->select('books.id, books.title, books.author, books.isbn')
            ->groupStart()->like('books.title', $q)->orLike('books.author', $q)->orLike('books.isbn', str_replace('-', '', $q))->groupEnd();

        if ($only === 'loans') {
            $b->where('EXISTS (SELECT 1 FROM borrowings x WHERE x.book_id = books.id)', null, false);
        } elseif ($only === 'fines') {
            $b->where('EXISTS (SELECT 1 FROM fines f JOIN borrowings x ON x.id = f.borrowing_id WHERE x.book_id = books.id)', null, false);
        }

        $rows = $b->orderBy($this->startsWith(['books.title', 'books.author'], $q), 'DESC', false)
            ->orderBy('books.title')->limit(self::LIMIT)->get()->getResultArray();

        return array_map(static fn ($r) => [
            'type'  => 'book',
            'label' => $r['title'],
            'sub'   => $r['author'] . ' · ISBN ' . $r['isbn'],
            // Loan lists match ISBNs exactly; the catalog and fines search by title.
            'value' => $only === 'loans' ? $r['isbn'] : $r['title'],
        ], $rows);
    }

    private function staff(string $q): array
    {
        $rows = db_connect()->table('users')
            ->select('name, id_number, role, position, status')
            ->whereIn('role', UserModel::STAFF_ROLES)
            ->groupStart()->like('name', $q)->orLike('id_number', $q)->orLike('email', $q)->groupEnd()
            ->orderBy($this->startsWith(['name', 'id_number'], $q), 'DESC', false)
            ->orderBy('name')->limit(self::LIMIT)->get()->getResultArray();

        return array_map(static fn ($u) => [
            'type'  => 'staff',
            'label' => $u['name'],
            'sub'   => $u['id_number'] . ' · ' . role_label($u['role']) . ($u['status'] !== 'active' ? ' · suspended' : ''),
            'value' => $u['id_number'],
        ], $rows);
    }
}
