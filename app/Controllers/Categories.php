<?php

namespace App\Controllers;

use App\Models\CategoryModel;

class Categories extends BaseController
{
    protected CategoryModel $categories;

    public function __construct()
    {
        helper('form');
        $this->categories = new CategoryModel();
    }

    public function index()
    {
        return view('categories/index', ['categories' => $this->categories->withBookCounts()]);
    }

    public function new()
    {
        return view('categories/form', ['category' => null]);
    }

    public function create()
    {
        return $this->persist(null);
    }

    public function edit(int $id)
    {
        $category = $this->categories->find($id);
        if (! $category) {
            return redirect()->to('/categories')->with('error', 'Category not found.');
        }

        return view('categories/form', ['category' => $category]);
    }

    public function update(int $id)
    {
        if (! $this->categories->find($id)) {
            return redirect()->to('/categories')->with('error', 'Category not found.');
        }

        return $this->persist($id);
    }

    public function delete(int $id)
    {
        $category = $this->categories->find($id);
        if (! $category) {
            return redirect()->to('/categories')->with('error', 'Category not found.');
        }

        // The foreign key sets books.category_id to NULL, so books are kept as "uncategorized".
        $this->categories->delete($id);

        return redirect()->to('/categories')->with('success', 'Category "' . $category['name'] . '" deleted. Its books are now uncategorized.');
    }

    private function persist(?int $id)
    {
        $rules = [
            'name'        => 'required|min_length[2]|max_length[100]',
            'description' => 'permit_empty|max_length[1000]',
        ];
        $errors = $this->validate($rules) ? [] : $this->validator->getErrors();

        $name = trim((string) $this->request->getPost('name'));
        if (! isset($errors['name']) && $this->categories->nameExists($name, $id)) {
            $errors['name'] = 'A category with this name already exists.';
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'name'        => $name,
            'description' => trim((string) $this->request->getPost('description')) ?: null,
        ];

        if ($id === null) {
            $this->categories->insert($data);
            $message = 'Category added.';
        } else {
            $this->categories->update($id, $data);
            $message = 'Category updated.';
        }

        return redirect()->to('/categories')->with('success', $message);
    }
}
