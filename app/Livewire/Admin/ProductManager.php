<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;

class ProductManager extends Component
{
    use WithPagination;

    // Form properties
    public $product_id;
    public $code;
    public $name;
    public $category = 'import';
    public $sub_category;
    public $service_type = 'service';
    public $description;
    public $default_price = 0;
    public $is_active = true;
    public $sort_order = 0;

    // UI state
    public $isModalOpen = false;
    public $isEditing = false;
    public $search = '';
    public $filterCategory = '';
    public $filterServiceType = '';

    protected $rules = [
        'code' => 'required|string|max:50|unique:products,code',
        'name' => 'required|string|max:255',
        'category' => 'required|in:import,export,domestic,consultation,reimbursement',
        'sub_category' => 'nullable|string|max:100',
        'service_type' => 'required|in:service,reimbursement',
        'description' => 'nullable|string',
        'default_price' => 'required|numeric|min:0',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function render()
    {
        $products = Product::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterCategory, function ($query) {
                $query->where('category', $this->filterCategory);
            })
            ->when($this->filterServiceType, function ($query) {
                $query->where('service_type', $this->filterServiceType);
            })
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(20);

        return view('livewire.admin.product-manager', [
            'products' => $products,
            'categories' => $this->getCategories(),
            'serviceTypes' => $this->getServiceTypes(),
        ])->layout('layouts.admin');
    }

    public function create()
    {
        $this->resetForm();
        $this->isModalOpen = true;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        
        $this->product_id = $product->id;
        $this->code = $product->code;
        $this->name = $product->name;
        $this->category = $product->category;
        $this->sub_category = $product->sub_category;
        $this->service_type = $product->service_type;
        $this->description = $product->description;
        $this->default_price = $product->default_price;
        $this->is_active = $product->is_active;
        $this->sort_order = $product->sort_order;
        
        $this->isModalOpen = true;
        $this->isEditing = true;
    }

    public function save()
    {
        if ($this->isEditing) {
            $this->rules['code'] = 'required|string|max:50|unique:products,code,' . $this->product_id;
        }

        $this->validate();

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'sub_category' => $this->sub_category,
            'service_type' => $this->service_type,
            'description' => $this->description,
            'default_price' => $this->default_price,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->isEditing) {
            Product::find($this->product_id)->update($data);
            session()->flash('message', 'Product updated successfully!');
        } else {
            Product::create($data);
            session()->flash('message', 'Product created successfully!');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        Product::find($id)->delete();
        session()->flash('message', 'Product deleted successfully!');
    }

    public function toggleActive($id)
    {
        $product = Product::find($id);
        $product->is_active = !$product->is_active;
        $product->save();
        
        session()->flash('message', 'Product status updated!');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->product_id = null;
        $this->code = '';
        $this->name = '';
        $this->category = 'import';
        $this->sub_category = '';
        $this->service_type = 'service';
        $this->description = '';
        $this->default_price = 0;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetErrorBag();
    }

    private function getCategories()
    {
        return [
            'import' => 'Import',
            'export' => 'Export',
            'domestic' => 'Domestic',
            'consultation' => 'Consultation',
            'reimbursement' => 'Reimbursement',
        ];
    }

    private function getServiceTypes()
    {
        return [
            'service' => 'Jasa/Service',
            'reimbursement' => 'Reimbursement',
        ];
    }
}
