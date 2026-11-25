<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    public const HEAD      = 'head_librarian';
    public const LIBRARIAN = 'librarian';
    public const STUDENT   = 'student';

    public const ROLES       = [self::HEAD, self::LIBRARIAN, self::STUDENT];
    public const STAFF_ROLES = [self::HEAD, self::LIBRARIAN];

    /** Courses offered in the "Course / Program" drop-down. Anything else is typed in via "Other". */
    public const COURSES = [
        'Bachelor of Science in Information Technology',
        'Bachelor of Science in Computer Science',
        'Bachelor of Science in Education',
        'Bachelor of Science in Hospitality Management',
    ];
    public const OTHER_COURSE = '__other';

    public const ROLE_LABELS = [
        self::HEAD      => 'Head Librarian',
        self::LIBRARIAN => 'Librarian Staff',
        self::STUDENT   => 'Student',
    ];

    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_number', 'name', 'email', 'phone', 'password', 'role', 'status', 'credits',
        'course', 'year_level', 'position', 'last_login_at',
    ];
    protected $useTimestamps = true;

    // "Archive" instead of delete: archived rows get archived_at and are left out of every query.
    protected $useSoftDeletes = true;
    protected $deletedField   = 'archived_at';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /** Hash the password when one is supplied; leave it untouched on updates that omit it. */
    protected function hashPassword(array $data): array
    {
        if (! isset($data['data']['password']) || $data['data']['password'] === '') {
            unset($data['data']['password']);

            return $data;
        }

        $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);

        return $data;
    }

    /** Find by Student/Staff ID number, or by email as a fallback. */
    public function findByLogin(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $field = str_contains($login, '@') ? 'email' : 'id_number';
        $value = $field === 'email' ? strtolower($login) : strtoupper($login);

        return $this->where($field, $value)->first();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /** Filtered list for the user / staff management screens. */
    public function search(array $roles, string $q = '', string $status = ''): self
    {
        $this->whereIn('role', $roles);

        if ($q !== '') {
            $this->groupStart()
                ->like('name', $q)
                ->orLike('id_number', $q)
                ->orLike('email', $q)
                ->orLike('course', $q)
                ->groupEnd();
        }
        if (in_array($status, ['active', 'suspended'], true)) {
            $this->where('status', $status);
        }

        // Suspended accounts first so they are easy to spot, then A–Z.
        return $this->orderBy("(status = 'suspended')", 'DESC', false)->orderBy('name', 'ASC');
    }

    /** Active students for the circulation drop-downs. */
    public function activeBorrowers(): array
    {
        return $this->select('id, id_number, name')
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public static function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst((string) $role);
    }
}
