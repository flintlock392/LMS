<?php

namespace App\Controllers;

use App\Models\CategoryModel;

/** Head Librarian: manage book categories (part of Book Management). */
class Categories extends BaseController
{
    protected CategoryModel $categories;

    public function __construct()
    {
        $this->categories = new CategoryModel();
    }

    public function index()
    {
        return view('categories/index', ['categories' => $this->categories->withBookCounts()]);
    }

    public function create()
    {
        return $this->persist(null);
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
        log_activity('book', 'category_deleted', 'Deleted category "' . $category['name'] . '"', 'category', $id);

        return redirect()->to('/categories')->with('success', 'Category "' . $category['name'] . '" deleted. Its books are now uncategorized.');
    }

    private function persist(?int $id)
    {
        $rules  = ['name' => 'required|min_length[2]|max_length[100]', 'description' => 'permit_empty|max_length[1000]'];
        $errors = $this->validate($rules) ? [] : $this->validator->getErrors();

        $name = trim((string) $this->request->getPost('name'));
        if (! isset($errors['name']) && $this->categories->nameExists($name, $id)) {
            $errors['name'] = 'A category with this name already exists.';
        }
        if ($errors) {
            return redirect()->to('/categories')->withInput()->with('errors', $errors);
        }

        $data = ['name' => $name, 'description' => trim((string) $this->request->getPost('description')) ?: null];

        if ($id === null) {
            $newId = (int) $this->categories->insert($data, true);
            log_activity('book', 'category_added', 'Added category "' . $name . '"', 'category', $newId);

            return redirect()->to('/categories')->with('success', 'Category added.');
        }
        $this->categories->update($id, $data);
        log_activity('book', 'category_edited', 'Edited category "' . $name . '"', 'category', $id);

        return redirect()->to('/categories')->with('success', 'Category updated.');
    }
}
