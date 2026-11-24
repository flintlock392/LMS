<?php

namespace App\Controllers;

use App\Models\SettingModel;

/** System Settings (Head Librarian only). */
class Settings extends BaseController
{
    public function index()
    {
        return view('settings/index', ['values' => (new SettingModel())->all(), 'defs' => SettingModel::DEFINITIONS]);
    }

    public function save()
    {
        $rules = [];
        foreach (SettingModel::DEFINITIONS as $key => [$label, , $type]) {
            $rules[$key] = ['label' => $label, 'rules' => match ($type) {
                'int'   => 'required|is_natural|less_than_equal_to[365]',
                'money' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100000]',
                'bool'  => 'permit_empty|in_list[0,1]',
                default => 'required|max_length[' . ($key === 'currency' ? 5 : 100) . ']',
            }];
        }
        $rules['loan_days']['rules']        = 'required|is_natural_no_zero|less_than_equal_to[365]';
        $rules['max_loans']['rules']        = 'required|is_natural_no_zero|less_than_equal_to[50]';
        $rules['hold_days']['rules']        = 'required|is_natural_no_zero|less_than_equal_to[30]';

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $values = [];
        foreach (SettingModel::DEFINITIONS as $key => [, , $type]) {
            $raw          = trim((string) $this->request->getPost($key));
            $values[$key] = match ($type) {
                'int'   => (string) (int) $raw,
                'money' => number_format((float) $raw, 2, '.', ''),
                'bool'  => $raw === '1' ? '1' : '0',
                default => $raw,
            };
        }
        (new SettingModel())->saveAll($values);

        return redirect()->to('/settings')->with('success', 'Settings saved. New rules apply to loans and fines from now on.');
    }
}
