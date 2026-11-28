<?php

namespace App\Models;

use CodeIgniter\Model;

/** Students' requests to have credits restored (appeal or library service). */
class CreditAppealModel extends Model
{
    public const TYPES = [
        'appeal'          => 'Appeal',
        'library_service' => 'Library service',
    ];

    protected $table         = 'credit_appeals';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'type', 'message', 'hours', 'status', 'reduced_by', 'response', 'handled_by', 'handled_at'];
    protected $useTimestamps = true;

    public function detailed(): self
    {
        return $this->select('credit_appeals.*, u.name AS student_name, u.id_number AS student_id_number, u.credits, u.course, h.name AS handled_by_name')
            ->join('users u', 'u.id = credit_appeals.user_id')
            ->join('users h', 'h.id = credit_appeals.handled_by', 'left');
    }

    public function pendingCount(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }
}
