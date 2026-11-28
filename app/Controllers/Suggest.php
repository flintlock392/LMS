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
            // Active students only (used by student pickers).
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
     * $only:  all | loans (has borrowed) | active (not suspended)
     * $match: any (name, number, email, course) | student_no | name
     */
    private function students(string $q, string $only, string $match = 'any'): array
    {
        $b = db_connect()->table('users')
            ->select('users.id, users.name, users.id_number, users.course, users.status, users.credits')
            ->where('users.role', UserModel::STUDENT)
            ->where('users.archived_at', null);

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
        }

        $rows = $b->orderBy($this->startsWith(['users.name', 'users.id_number'], $q), 'DESC', false)
            ->orderBy('users.name')->limit(self::LIMIT)->get()->getResultArray();

        return array_map(static fn ($u) => [
            'type'  => 'student',
            'id'    => (int) $u['id'],
            // Searching by number shows the number first; otherwise the name.
            'label' => $match === 'student_no' ? $u['id_number'] : $u['name'],
            'sub'   => ($match === 'student_no' ? $u['name'] : $u['id_number']) . ($u['course'] ? ' · ' . $u['course'] : '')
                . ((int) $u['credits'] < \App\Libraries\CreditPolicy::MAX ? ' · ' . (int) $u['credits'] . ' credits' : '')
                . ($u['status'] !== 'active' ? ' · suspended' : ''),
            'value' => $u['id_number'],   // a student number matches exactly one student
            'pick'  => $u['name'] . ' (' . $u['id_number'] . ')',
            'credits' => (int) $u['credits'],
        ], $rows);
    }

    /** $only: all | loans (has been borrowed) */
    private function books(string $q, string $only): array
    {
        $b = db_connect()->table('books')
            ->select('books.id, books.title, books.author, books.isbn')
            ->where('books.archived_at', null)
            ->groupStart()->like('books.title', $q)->orLike('books.author', $q)->orLike('books.isbn', str_replace('-', '', $q))->groupEnd();

        if ($only === 'loans') {
            $b->where('EXISTS (SELECT 1 FROM borrowings x WHERE x.book_id = books.id)', null, false);
        }

        $rows = $b->orderBy($this->startsWith(['books.title', 'books.author'], $q), 'DESC', false)
            ->orderBy('books.title')->limit(self::LIMIT)->get()->getResultArray();

        return array_map(static fn ($r) => [
            'type'  => 'book',
            'label' => $r['title'],
            'sub'   => $r['author'] . ' · ISBN ' . $r['isbn'],
            // Loan lists match ISBNs exactly; the catalog searches by title.
            'value' => $only === 'loans' ? $r['isbn'] : $r['title'],
        ], $rows);
    }

    private function staff(string $q): array
    {
        $rows = db_connect()->table('users')
            ->select('name, id_number, role, position, status')
            ->whereIn('role', UserModel::STAFF_ROLES)
            ->where('archived_at', null)
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
