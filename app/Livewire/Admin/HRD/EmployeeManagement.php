<?php

namespace App\Livewire\Admin\HRD;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Employee;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class EmployeeManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterStatus = '';
    public $perPage = 25;

    // Modal state
    public $isModalOpen = false;
    public $isEditing = false;
    public $employeeId = null;

    // Form fields
    public $nik = '';
    public $nama = '';
    public $jabatan_id = '';
    public $join_date = '';
    public $status = 'active';
    public $employment_type = 'permanent';
    public $no_hp = '';
    public $alamat = '';
    public $user_id = null;

    // Image uploads (Livewire temp files)
    public $photo_upload = null;
    public $ktp_upload = null;
    public $bpjs_upload = null;

    // Current saved paths (for display while editing)
    public $current_photo = null;
    public $current_ktp = null;
    public $current_bpjs = null;

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId = null;

    // Image preview modal
    public $previewImage = null;
    public $previewTitle = '';

    protected $queryString = ['search', 'filterStatus'];

    protected function rules(): array
    {
        $nikRule = $this->isEditing
            ? 'required|string|max:50|unique:employees,nik,' . $this->employeeId
            : 'required|string|max:50|unique:employees,nik';

        return [
            'nik'           => $nikRule,
            'nama'          => 'required|string|max:150',
            'jabatan_id'    => 'required|exists:jabatan,id',
            'join_date'     => 'required|date',
            'status'        => 'required|in:active,inactive',
            'no_hp'         => 'nullable|string|max:20',
            'alamat'        => 'nullable|string',
            'photo_upload'  => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'ktp_upload'    => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'bpjs_upload'   => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // --- Access Control ---
    public function canCreate(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance']);
    }

    public function canEdit(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance']);
    }

    public function canDelete(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin']);
    }

    // --- CRUD ---
    public function openCreate(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->reset([
            'employeeId', 'nik', 'nama', 'jabatan_id', 'join_date',
            'status', 'employment_type', 'no_hp', 'alamat', 'user_id',
            'photo_upload', 'ktp_upload', 'bpjs_upload',
            'current_photo', 'current_ktp', 'current_bpjs',
        ]);
        $this->status      = 'active';
        $this->join_date   = now()->format('Y-m-d');
        $this->isEditing   = false;
        $this->isModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canEdit(), 403);
        $emp = Employee::findOrFail($id);

        $this->employeeId      = $emp->id;
        $this->nik             = $emp->nik;
        $this->nama            = $emp->nama;
        $this->jabatan_id      = $emp->jabatan_id;
        $this->join_date       = $emp->join_date->format('Y-m-d');
        $this->status          = $emp->status;
        $this->employment_type = $emp->employment_type;
        $this->no_hp           = $emp->no_hp;
        $this->alamat          = $emp->alamat;
        $this->user_id         = $emp->user_id;

        // Show current images
        $this->current_photo = $emp->photo;
        $this->current_ktp   = $emp->ktp_image;
        $this->current_bpjs  = $emp->bpjs_image;

        // Reset new upload slots
        $this->photo_upload = null;
        $this->ktp_upload   = null;
        $this->bpjs_upload  = null;

        $this->isEditing   = true;
        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nik'             => $this->nik,
            'nama'            => $this->nama,
            'jabatan_id'      => $this->jabatan_id,
            'join_date'       => $this->join_date,
            'status'          => $this->status,
            'employment_type' => $this->employment_type,
            'no_hp'           => $this->no_hp,
            'alamat'          => $this->alamat,
            'user_id'         => $this->user_id ?: null,
        ];

        // Retrieve existing paths when editing (so we can delete old files)
        $existing = $this->isEditing ? Employee::find($this->employeeId) : null;

        // Process each image upload
        if ($this->photo_upload) {
            if ($existing?->photo) {
                Storage::disk('public')->delete($existing->photo);
            }
            $data['photo'] = $this->processImage($this->photo_upload, 'employees/photo', 400, 500);
        }

        if ($this->ktp_upload) {
            if ($existing?->ktp_image) {
                Storage::disk('public')->delete($existing->ktp_image);
            }
            $data['ktp_image'] = $this->processImage($this->ktp_upload, 'employees/ktp', 1200, null);
        }

        if ($this->bpjs_upload) {
            if ($existing?->bpjs_image) {
                Storage::disk('public')->delete($existing->bpjs_image);
            }
            $data['bpjs_image'] = $this->processImage($this->bpjs_upload, 'employees/bpjs', 1200, null);
        }

        if ($this->isEditing) {
            abort_unless($this->canEdit(), 403);
            Employee::findOrFail($this->employeeId)->update($data);
            session()->flash('success', 'Data karyawan berhasil diperbarui.');
        } else {
            abort_unless($this->canCreate(), 403);
            Employee::create($data);
            session()->flash('success', 'Karyawan berhasil ditambahkan.');
        }

        $this->isModalOpen = false;
    }

    /**
     * Resize & save uploaded image, return relative storage path.
     *
     * @param  \Livewire\Features\SupportFileUploads\TemporaryUploadedFile  $file
     * @param  string  $folder       Storage subfolder under public disk
     * @param  int     $maxWidth     Max width in pixels
     * @param  int|null $maxHeight   Max height in pixels (null = proportional)
     * @return string  Path relative to storage/app/public
     */
    private function processImage($file, string $folder, int $maxWidth, ?int $maxHeight): string
    {
        Storage::disk('public')->makeDirectory($folder);

        $filename = uniqid() . '_' . time() . '.jpg';
        $path     = storage_path("app/public/{$folder}/{$filename}");

        $image = Image::read($file->getRealPath());

        if ($maxHeight) {
            // Portrait resize (pas photo): fit within box, keeping aspect ratio
            $image->scaleDown(width: $maxWidth, height: $maxHeight);
        } else {
            // Landscape resize (KTP/BPJS): limit width only
            $image->scaleDown(width: $maxWidth);
        }

        $image->toJpeg(quality: 82)->save($path);

        return "{$folder}/{$filename}";
    }

    public function removeImage(string $type): void
    {
        abort_unless($this->canEdit(), 403);

        if (!$this->employeeId) return;

        $emp   = Employee::findOrFail($this->employeeId);
        $field = match ($type) {
            'photo' => 'photo',
            'ktp'   => 'ktp_image',
            'bpjs'  => 'bpjs_image',
            default => null,
        };

        if ($field && $emp->$field) {
            Storage::disk('public')->delete($emp->$field);
            $emp->update([$field => null]);

            // Sync current_ properties
            $this->{'current_' . ($type === 'photo' ? 'photo' : $type)} = null;
            if ($type === 'ktp')  $this->current_ktp  = null;
            if ($type === 'bpjs') $this->current_bpjs = null;
            if ($type === 'photo') $this->current_photo = null;

            session()->flash('success', 'Gambar berhasil dihapus.');
        }
    }

    public function openPreview(string $path, string $title): void
    {
        $this->previewImage = $path;
        $this->previewTitle = $title;
    }

    public function closePreview(): void
    {
        $this->previewImage = null;
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        abort_unless($this->canDelete(), 403);
        $emp = Employee::findOrFail($this->deleteId);

        // Delete associated images from storage
        foreach (['photo', 'ktp_image', 'bpjs_image'] as $field) {
            if ($emp->$field) {
                Storage::disk('public')->delete($emp->$field);
            }
        }

        $emp->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Karyawan berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->isModalOpen       = false;
        $this->showDeleteConfirm = false;
    }

    public function render()
    {
        $employees = Employee::with('jabatan')
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('nama', 'like', "%{$this->search}%")
                  ->orWhere('nik', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('nama')
            ->paginate($this->perPage);

        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();

        return view('livewire.admin.hrd.employee-management', [
            'employees'   => $employees,
            'jabatanList' => $jabatanList,
        ])->layout('layouts.admin');
    }
}
