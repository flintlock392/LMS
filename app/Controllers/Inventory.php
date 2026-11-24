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

        return redirect()->to('/library?tab=collections')->with('success', 'Inventory updated. The title now has ' . $new . ' cop' . ($new === 1 ? 'y' : 'ies') . '.');
    }
}
