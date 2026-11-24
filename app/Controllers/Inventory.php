<?php

namespace App\Controllers;

use App\Libraries\Circulation as CirculationService;
use App\Models\BookModel;
use App\Models\InventoryLogModel;
use RuntimeException;

/**
 * Head Librarian: "Inventory Management" (add/remove copies with a reason).
 * Librarian Staff: "Inventory Monitoring" (read-only stock levels and history).
 */
class Inventory extends BaseController
{
    public function index()
    {
        $filters = [
            'q'            => trim((string) $this->request->getGet('q')),
            'availability' => (string) $this->request->getGet('availability'),
            'sort'         => 'available',
            'dir'          => 'asc',
        ];

        $model = new BookModel();

        return view('inventory/index', [
            'books'   => $model->filtered($filters)->paginate(15),
            'pager'   => $model->pager,
            'filters' => $filters,
            'stats'   => (new BookModel())->stats(),
            'log'     => (new InventoryLogModel())->recent(12),
            'all'     => is_head() ? (new BookModel())->forSelect() : [],
        ]);
    }

    /** Head Librarian only. */
    public function adjust()
    {
        $direction = (string) $this->request->getPost('direction');
        $qty       = (int) $this->request->getPost('quantity');
        $reason    = (string) $this->request->getPost('reason');

        $valid = $direction === 'add' ? InventoryLogModel::ADD_REASONS : InventoryLogModel::REMOVE_REASONS;
        if (! array_key_exists($reason, $valid + ['correction' => ''])) {
            return redirect()->back()->withInput()->with('error', 'Choose a reason for the change.');
        }
        if ($qty < 1 || $qty > 1000) {
            return redirect()->back()->withInput()->with('error', 'Enter a quantity between 1 and 1000.');
        }

        try {
            $new = (new CirculationService())->adjustStock(
                (int) $this->request->getPost('book_id'),
                $direction === 'add' ? $qty : -$qty,
                $reason,
                (int) session()->get('userId'),
                trim((string) $this->request->getPost('notes'))
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/inventory')->with('success', 'Inventory updated. The title now has ' . $new . ' cop' . ($new === 1 ? 'y' : 'ies') . '.');
    }
}
