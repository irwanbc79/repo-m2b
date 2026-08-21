<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Shipment;
use App\Models\ShipmentEtaRevision;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Mail\ShipmentStatusUpdated;
use Illuminate\Support\Facades\Mail;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShipmentManagement extends Component
{
    use WithPagination, WithFileUploads;

    // Search & Filter
    public $search = '';
    public $filterStatus = '';
    public $filterCustomer = '';
    public $filterServiceType = '';
    public $filterShipmentType = '';
    public $filterLaneStatus = '';
    public $filterCustomerData = ''; // '' | 'attention' = shipment milik customer yg datanya perlu dilengkapi
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;

    // Modal States
    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null;
    public $showQuickView = false;
    public $quickViewShipment = null;
    public $showDeleteConfirm = false;
    public $deleteId = null;

    // ETA Revision Modal
    public $showEtaRevisionModal = false;
    public $etaRevisionShipment = null;
    public $etaRevisedDate = '';
    public $etaReasonCode = '';
    public $etaReasonNotes = '';
    public $etaSourceParty = '';
    public $etaInformationReceivedAt = '';
    public $etaCustomerVisible = false;
    public $etaCustomerMessage = '';
    public $etaEvidenceCustomerVisible = false;
    public $etaEvidence;

    // Publication-only editor: data historis revisi tetap immutable.
    public $showEtaPublicationModal = false;
    public $etaPublicationRevision = null;
    public $publicationCustomerVisible = false;
    public $publicationCustomerMessage = '';
    public $publicationEvidenceVisible = false;

    // Bulk Actions
    public $selectedShipments = [];
    public $selectAll = false;
    public $bulkStatus = '';

    // Form Data
    public $form = [
        'customer_id' => '',
        'awb_number' => '',
        'origin' => '',
        'destination' => '',
        'service_type' => 'import',
        'shipment_type' => 'sea',
        'container_mode' => 'FCL',
        'container_info' => '',
        'pieces' => 0,
        'package_type' => 'Colli',
        'weight' => 0,
        'volume' => 0,
        'commodity' => '',
        'hs_code' => '',
        'status' => 'pending',
        'lane_status' => '',
        'estimated_arrival' => '',
        'notes' => ''
    ];
    
    public $mark_as_completed = false;

    public $uploadingShipmentId = null;
    public $internal_photo;
    public $internal_note;

    // Modal Cetak Surat Jalan
    public $showPrintDoModal = false;
    public $printDoShipmentId = null;
    public $printDoShipment = null;
    public $printDoContainerCount = 1;
    public $printDoContainers = [];
    public $printDoAlamatBongkar = '';
    public $printDoNamaPenerima = '';

    protected $queryString = ['search', 'filterStatus', 'filterCustomer', 'filterServiceType', 'filterLaneStatus', 'filterCustomerData', 'sortField', 'sortDirection'];

    public function mount(): void
    {
        $etaRevisionId = request()->integer('eta_revision');

        if ($etaRevisionId > 0) {
            $this->openEtaRevisionModal($etaRevisionId);
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterCustomer() { $this->resetPage(); }
    public function updatingFilterServiceType() { $this->resetPage(); }
    public function updatingFilterLaneStatus() { $this->resetPage(); }
    public function updatingFilterCustomerData() { $this->resetPage(); }
    public function updatingFilterShipmentType() { $this->resetPage(); }

    /**
     * Buang semua penyaring sekaligus.
     *
     * Dipakai tombol "Bersihkan" di bilah penyaring. Pencarian ikut dibuang
     * karena dari sisi pengguna keduanya sama-sama "kenapa datanya tinggal
     * sedikit" — memisahkannya justru bikin orang mengira masih ada saringan
     * tersembunyi.
     */
    public function resetFilters()
    {
        $this->reset([
            'search',
            'filterStatus',
            'filterShipmentType',
            'filterServiceType',
            'filterLaneStatus',
            'filterCustomerData',
        ]);

        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedShipments = $this->getShipmentsQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedShipments = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getStats()
    {
        return Cache::remember('shipment_stats', 300, function() {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();

            return [
            'total' => Shipment::count(),
            'pending' => Shipment::where('status', 'pending')->count(),
            'in_progress' => Shipment::where('status', 'in_progress')->count(),
            'in_transit' => Shipment::where('status', 'in_transit')->count(),
            'completed' => Shipment::where('status', 'completed')->count(),
            'this_month' => Shipment::where('created_at', '>=', $startOfMonth)->count(),
            'total_weight' => Shipment::sum('weight'),
            'total_volume' => Shipment::sum('volume'),
            'total_pieces' => Shipment::sum('pieces')];
        });
    }

    public function getCustomersList()
    {
        return Customer::orderBy('company_name')->get(['id', 'company_name', 'customer_code']);
    }

    private function getShipmentsQuery()
    {
        $searchTerm = '%' . $this->search . '%';
        
        return Shipment::with([
            'customer',
            'etaRevisions' => fn ($query) => $query->with(['creator', 'sourceDocument']),
        ])
            
            ->when($this->search, function($q) use ($searchTerm) {
                $q->where(function($query) use ($searchTerm) {
                    $query->where('awb_number', 'like', $searchTerm)
                          ->orWhere('origin', 'like', $searchTerm)
                          ->orWhere('destination', 'like', $searchTerm)
                          ->orWhere('commodity', 'like', $searchTerm)
                          ->orWhereHas('customer', function($c) use ($searchTerm) {
                              $c->where('company_name', 'like', $searchTerm)
                                ->orWhere('customer_code', 'like', $searchTerm);
                          });
                });
            })
            ->when($this->filterStatus, function($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterCustomer, function($q) {
                $q->where('customer_id', $this->filterCustomer);
            })
            ->when($this->filterServiceType, function($q) {
                $q->where('service_type', $this->filterServiceType);
            })
            ->when($this->filterShipmentType, function($q) {
                $q->where('shipment_type', $this->filterShipmentType);
            })
            ->when($this->filterLaneStatus, function($q) {
                $q->where('lane_status', $this->filterLaneStatus);
            })
            ->when($this->filterCustomerData === 'attention', function($q) {
                $q->whereHas('customer', function($c) {
                    $c->needsAttention();
                });
            })
            ->when($this->filterDateFrom, function($q) {
                $q->whereDate('created_at', '>=', $this->filterDateFrom);
            })
            ->when($this->filterDateTo, function($q) {
                $q->whereDate('created_at', '<=', $this->filterDateTo);
            })
            ->orderByRaw("CASE WHEN status = 'completed' OR status = 'cancel' THEN 1 ELSE 0 END")
            ->orderBy($this->sortField, $this->sortDirection);
    }

    
    // Cancel Shipment Properties
    public $showCancelModal = false;
    public $shipmentToCancel = null;
    public $cancellationReason = '';
    public $showCancelReasonModal = false;
    public $cancelReasonShipment = null;

    /**
     * Open cancel modal
     */
    public function openCancelModal($shipmentId)
    {
        $shipment = \App\Models\Shipment::find($shipmentId);
        
        if (!$shipment) {
            session()->flash('error', 'Shipment tidak ditemukan.');
            return;
        }
        
        if ($shipment->status === 'cancel') {
            session()->flash('error', 'Shipment sudah dibatalkan.');
            return;
        }
        
        $this->shipmentToCancel = $shipmentId;
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    /**
     * Close cancel modal
     */
    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->shipmentToCancel = null;
        $this->cancellationReason = '';
    }

    /**
     * Confirm and execute cancel
     */
    public function confirmCancel()
    {
        if (!$this->shipmentToCancel) {
            $this->closeCancelModal();
            return;
        }

        $shipment = \App\Models\Shipment::find($this->shipmentToCancel);
        
        if (!$shipment) {
            session()->flash('error', 'Shipment tidak ditemukan.');
            $this->closeCancelModal();
            return;
        }

        if ($shipment->status === 'cancel') {
            session()->flash('error', 'Shipment sudah dibatalkan.');
            $this->closeCancelModal();
            return;
        }

        // Cancel the shipment
        $shipment->update([
            'status' => 'cancel',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancellation_reason' => $this->cancellationReason,
        ]);

        session()->flash('success', "Shipment {$shipment->awb_number} berhasil dibatalkan.");
        
        $this->closeCancelModal();
    }

    public function render()
    {
        $shipments = $this->getShipmentsQuery()->paginate($this->perPage);
        $customers = $this->getCustomersList();
        $stats = $this->getStats();

        return view('livewire.admin.shipment-management', [
            'shipments' => $shipments,
            'customers' => $customers,
            'stats' => $stats,
            'etaReasonOptions' => ShipmentEtaRevision::reasonOptions(),
        ])->layout('layouts.admin');
    }

    public function openEtaRevisionModal(int $shipmentId): void
    {
        abort_unless(Auth::user()->hasPermission('shipment.edit'), 403);

        $this->resetValidation();
        $this->resetEtaRevisionForm();
        $this->etaRevisionShipment = Shipment::with('etaRevisions.creator')->findOrFail($shipmentId);
        $this->etaInformationReceivedAt = now()->format('Y-m-d\TH:i');
        $this->showEtaRevisionModal = true;
    }

    public function closeEtaRevisionModal(): void
    {
        $this->showEtaRevisionModal = false;
        $this->etaRevisionShipment = null;
        $this->resetEtaRevisionForm();
        $this->resetValidation();
    }

    public function saveEtaRevision(): void
    {
        abort_unless(Auth::user()->hasPermission('shipment.edit'), 403);

        $this->validate([
            'etaRevisedDate' => 'required|date',
            'etaReasonCode' => 'required|in:' . implode(',', array_keys(ShipmentEtaRevision::reasonOptions())),
            'etaReasonNotes' => 'nullable|string|max:1000',
            'etaSourceParty' => 'nullable|string|max:150',
            'etaInformationReceivedAt' => 'required|date',
            'etaCustomerVisible' => 'boolean',
            'etaCustomerMessage' => 'nullable|required_if:etaCustomerVisible,true|string|max:1000',
            'etaEvidenceCustomerVisible' => 'boolean',
            'etaEvidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $shipment = Shipment::findOrFail($this->etaRevisionShipment->id);
        $previousEta = $shipment->estimated_arrival?->copy()->startOfDay();
        $revisedEta = Carbon::parse($this->etaRevisedDate)->startOfDay();
        $changeDays = $previousEta
            ? (int) $previousEta->diffInDays($revisedEta, false)
            : 0;

        if ($previousEta && $previousEta->equalTo($revisedEta)) {
            $this->addError('etaRevisedDate', 'ETA terbaru harus berbeda dari ETA saat ini.');
            return;
        }

        $storedPath = null;

        try {
            if ($this->etaEvidence) {
                $storedPath = $this->etaEvidence->store("documents/eta_revisions/{$shipment->id}", 'public');
            }

            DB::transaction(function () use ($shipment, $previousEta, $revisedEta, $changeDays, $storedPath) {
                $document = null;

                if ($storedPath) {
                    $document = Document::create([
                        'shipment_id' => $shipment->id,
                        'document_type' => 'other',
                        'filename' => basename($storedPath),
                        'file_path' => $storedPath,
                        'description' => 'Bukti revisi ETA: ' . ShipmentEtaRevision::reasonOptions()[$this->etaReasonCode],
                        'is_internal' => !$this->etaEvidenceCustomerVisible,
                        'is_public' => $this->etaEvidenceCustomerVisible,
                        'uploaded_by' => Auth::id(),
                        'file_size' => $this->etaEvidence->getSize(),
                        'mime_type' => $this->etaEvidence->getMimeType(),
                        'uploaded_at' => now(),
                    ]);
                }

                ShipmentEtaRevision::create([
                    'shipment_id' => $shipment->id,
                    'previous_eta' => $previousEta,
                    'revised_eta' => $revisedEta,
                    'change_days' => $changeDays,
                    'reason_code' => $this->etaReasonCode,
                    'reason_notes' => $this->etaReasonNotes ?: null,
                    'source_party' => $this->etaSourceParty ?: null,
                    'information_received_at' => Carbon::parse($this->etaInformationReceivedAt),
                    'source_document_id' => $document?->id,
                    'customer_visible' => $this->etaCustomerVisible,
                    'customer_message' => $this->etaCustomerVisible ? $this->etaCustomerMessage : null,
                    'evidence_customer_visible' => $this->etaEvidenceCustomerVisible,
                    'published_at' => $this->etaCustomerVisible ? now() : null,
                    'created_by' => Auth::id(),
                ]);

                $shipment->update(['estimated_arrival' => $revisedEta]);
            });

            try {
                ActivityLog::record(
                    'Shipment',
                    'REVISI ETA',
                    $shipment->awb_number,
                    sprintf(
                        'ETA %s → %s (%+d hari): %s',
                        $previousEta?->format('d M Y') ?? 'belum diisi',
                        $revisedEta->format('d M Y'),
                        $changeDays,
                        ShipmentEtaRevision::reasonOptions()[$this->etaReasonCode]
                    ),
                    ['estimated_arrival' => $previousEta?->toDateString()],
                    ['estimated_arrival' => $revisedEta->toDateString()]
                );
            } catch (\Throwable $auditError) {
                report($auditError);
            }
        } catch (\Throwable $e) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }

            if (app()->runningUnitTests()) {
                throw $e;
            }

            report($e);
            session()->flash('error', 'Revisi ETA gagal disimpan. Silakan coba kembali.');
            return;
        }

        $this->closeEtaRevisionModal();
        session()->flash('message', 'Revisi ETA berhasil disimpan beserta riwayatnya.');
    }

    private function resetEtaRevisionForm(): void
    {
        $this->reset([
            'etaRevisedDate',
            'etaReasonCode',
            'etaReasonNotes',
            'etaSourceParty',
            'etaInformationReceivedAt',
            'etaCustomerVisible',
            'etaCustomerMessage',
            'etaEvidenceCustomerVisible',
            'etaEvidence',
        ]);
    }

    public function openEtaPublicationModal(int $revisionId): void
    {
        abort_unless(Auth::user()->hasPermission('shipment.edit'), 403);

        $revision = ShipmentEtaRevision::with(['shipment.customer', 'sourceDocument'])
            ->findOrFail($revisionId);

        $this->resetValidation();
        $this->etaPublicationRevision = $revision;
        $this->publicationCustomerVisible = (bool) $revision->customer_visible;
        $this->publicationCustomerMessage = (string) ($revision->customer_message ?? '');
        $this->publicationEvidenceVisible = (bool) $revision->evidence_customer_visible;
        if ($this->publicationCustomerMessage === '') {
            $this->publicationCustomerMessage = $this->buildEtaCustomerMessage($revision);
        }
        $this->showEtaPublicationModal = true;
    }

    public function applyEtaPublicationTemplate(): void
    {
        abort_unless(Auth::user()->hasPermission('shipment.edit'), 403);

        $revision = ShipmentEtaRevision::with('shipment')->findOrFail($this->etaPublicationRevision->id);
        $this->publicationCustomerMessage = $this->buildEtaCustomerMessage($revision);
        $this->resetValidation('publicationCustomerMessage');
    }

    public function resetEtaPublicationMessage(): void
    {
        $this->publicationCustomerMessage = '';
        $this->resetValidation('publicationCustomerMessage');
    }

    private function buildEtaCustomerMessage(ShipmentEtaRevision $revision): string
    {
        $reasons = [
            'carrier_schedule' => 'terdapat perubahan jadwal operasional dari shipping line atau maskapai',
            'rollover' => 'terdapat penyesuaian jadwal pengangkutan atau vessel rollover',
            'port_congestion' => 'terjadi kepadatan operasional di pelabuhan atau bandara',
            'weather' => 'terdapat kondisi cuaca yang memengaruhi jadwal perjalanan',
            'customs' => 'proses penyelesaian kepabeanan masih berlangsung',
            'transshipment' => 'terdapat penyesuaian jadwal pada proses transshipment',
            'documents' => 'proses kelengkapan dan verifikasi dokumen masih berlangsung',
            'vendor' => 'terdapat penyesuaian jadwal operasional vendor atau trucking',
            'customer_request' => 'terdapat penyesuaian sesuai permintaan customer',
            'other' => 'terdapat penyesuaian jadwal operasional',
        ];

        $reference = $revision->shipment?->awb_number ?? 'shipment ini';
        $previousEta = $revision->previous_eta?->format('d M Y') ?? 'estimasi sebelumnya';
        $revisedEta = $revision->revised_eta->format('d M Y');
        $reason = $reasons[$revision->reason_code] ?? $reasons['other'];
        $source = $revision->source_party
            ? ' Informasi ini kami terima dari ' . $revision->source_party . '.'
            : '';

        return "Estimasi tiba shipment {$reference} diperbarui dari {$previousEta} menjadi {$revisedEta} karena {$reason}.{$source} Tim M2B terus memantau pengiriman dan akan menyampaikan pembaruan berikutnya apabila tersedia.";
    }

    public function closeEtaPublicationModal(): void
    {
        $this->showEtaPublicationModal = false;
        $this->etaPublicationRevision = null;
        $this->reset([
            'publicationCustomerVisible',
            'publicationCustomerMessage',
            'publicationEvidenceVisible',
        ]);
        $this->resetValidation();
    }

    public function saveEtaPublication(): void
    {
        abort_unless(Auth::user()->hasPermission('shipment.edit'), 403);

        $this->validate([
            'publicationCustomerVisible' => 'boolean',
            'publicationCustomerMessage' => 'nullable|required_if:publicationCustomerVisible,true|string|max:1000',
            'publicationEvidenceVisible' => 'boolean',
        ]);

        if ($this->publicationEvidenceVisible && !$this->publicationCustomerVisible) {
            $this->addError('publicationEvidenceVisible', 'Publikasikan revisi terlebih dahulu sebelum membagikan bukti.');
            return;
        }

        $revision = ShipmentEtaRevision::with(['shipment', 'sourceDocument'])
            ->findOrFail($this->etaPublicationRevision->id);
        $wasVisible = (bool) $revision->customer_visible;

        DB::transaction(function () use ($revision, $wasVisible) {
            $revision->update([
                'customer_visible' => $this->publicationCustomerVisible,
                'customer_message' => $this->publicationCustomerVisible ? $this->publicationCustomerMessage : null,
                'evidence_customer_visible' => $this->publicationEvidenceVisible,
                'published_at' => $this->publicationCustomerVisible
                    ? ($wasVisible && $revision->published_at ? $revision->published_at : now())
                    : null,
                'viewed_at' => $this->publicationCustomerVisible ? $revision->viewed_at : null,
            ]);

            if ($revision->sourceDocument) {
                $revision->sourceDocument->update([
                    'is_public' => $this->publicationEvidenceVisible,
                    'is_internal' => !$this->publicationEvidenceVisible,
                ]);
            }
        });

        try {
            ActivityLog::record(
                'Shipment',
                $this->publicationCustomerVisible ? 'PUBLIKASIKAN REVISI ETA' : 'TARIK PUBLIKASI REVISI ETA',
                $revision->shipment->awb_number,
                sprintf(
                    'Revisi ETA %s → %s; bukti customer: %s',
                    $revision->previous_eta?->format('d M Y') ?? 'belum diisi',
                    $revision->revised_eta->format('d M Y'),
                    $this->publicationEvidenceVisible ? 'ya' : 'tidak'
                )
            );
        } catch (\Throwable $auditError) {
            report($auditError);
        }

        $this->closeEtaPublicationModal();
        session()->flash('message', 'Pengaturan publikasi revisi ETA berhasil diperbarui.');
    }

    public function quickView($id)
    {
        $this->quickViewShipment = Shipment::with([
            'customer',
            'documents',
            'documentRequirements',
            'etaRevisions.creator',
            'etaRevisions.sourceDocument',
        ])
            ->find($id);
        $this->showQuickView = true;
    }

    public function closeQuickView()
    {
        $this->showQuickView = false;
        $this->quickViewShipment = null;
    }

    public function reviseEtaFromQuickView(int $shipmentId): void
    {
        $this->closeQuickView();
        $this->openEtaRevisionModal($shipmentId);
    }

    public function quickStatusUpdate($id, $status)
    {
        $shipment = Shipment::find($id);
        if ($shipment) {
            $oldStatus = $shipment->status;
            $shipment->update(['status' => $status]);
            
            if ($oldStatus !== $status) {
                ActivityLog::record('Shipment', 'UPDATE STATUS', $shipment->awb_number, "Status: {$oldStatus} → {$status}");
                
                // Send email notification
                $shipment->load('customer.user');
                if ($shipment->customer && $shipment->customer->user) {
                    try {
                        Mail::to($shipment->customer->user->email)->send(new ShipmentStatusUpdated($shipment)); \App\Models\SentEmail::create(['mailbox' => 'no_reply', 'to_email' => $shipment->customer->user->email, 'subject' => 'Status Update: ' . $shipment->awb_number, 'body' => 'Status shipment diupdate ke ' . $shipment->status, 'user_id' => auth()->id(), 'user_name' => auth()->user()->name]);
                    } catch (\Exception $e) {}
                }
            }
            
            session()->flash('message', "Status shipment {$shipment->awb_number} berhasil diupdate!");
        }
    }

    public function bulkStatusUpdate()
    {
        if (empty($this->selectedShipments) || empty($this->bulkStatus)) {
            session()->flash('error', 'Pilih shipment dan status terlebih dahulu.');
            return;
        }

        $updated = 0;
        foreach ($this->selectedShipments as $id) {
            $shipment = Shipment::find($id);
            if ($shipment && $shipment->status !== $this->bulkStatus) {
                $oldStatus = $shipment->status;
                $shipment->update(['status' => $this->bulkStatus]);
                ActivityLog::record('Shipment', 'BULK UPDATE', $shipment->awb_number, "Status: {$oldStatus} → {$this->bulkStatus}");
                $updated++;
            }
        }

        $this->selectedShipments = [];
        $this->selectAll = false;
        $this->bulkStatus = '';
        
        session()->flash('message', "{$updated} shipment berhasil diupdate statusnya.");
    }

    public function exportExcel()
    {
        $shipments = $this->getShipmentsQuery()->get();

        $csvContent = "AWB Number,Customer,Origin,Destination,Service,Type,Mode,Status,Weight (Kg),Volume (CBM),Pieces,ETA,Created\n";

        foreach ($shipments as $s) {
            $csvContent .= implode(',', [
                $s->awb_number,
                '"' . str_replace('"', '""', $s->customer->company_name ?? '-') . '"',
                '"' . str_replace('"', '""', $s->origin) . '"',
                '"' . str_replace('"', '""', $s->destination) . '"',
                $s->service_type,
                $s->shipment_type,
                $s->container_mode ?? '-',
                $s->status,
                $s->weight ?? 0,
                $s->volume ?? 0,
                $s->pieces ?? 0,
                $s->estimated_arrival ? date('Y-m-d', strtotime($s->estimated_arrival)) : '-',
                $s->created_at?->format('Y-m-d') ?? '-']) . "\n";
        }

        $filename = 'shipments_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csvContent) {
            echo "\xEF\xBB\xBF" . $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $this->editingId = $id;
        $shipment = Shipment::findOrFail($id);
        
        $this->form = $shipment->only([
            'customer_id', 'awb_number', 'origin', 'destination', 
            'service_type', 'shipment_type', 'container_mode', 'container_info',
            'pieces', 'package_type', 'weight', 'volume', 'commodity', 'hs_code', 'status', 'lane_status', 'estimated_arrival', 'notes'
        ]);
        
        if($this->form['estimated_arrival']) {
            $this->form['estimated_arrival'] = date('Y-m-d', strtotime($this->form['estimated_arrival']));
        }
        
        $this->mark_as_completed = ($this->form['status'] === 'completed');

        $this->isEditing = true;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'form.customer_id' => 'required',
            'form.origin' => 'required',
            'form.destination' => 'required',
            'form.service_type' => 'required',
            'form.weight' => 'required|numeric',
            'form.volume' => 'nullable|numeric|min:0',
            'form.pieces' => 'required|numeric',
            'form.package_type' => 'required',
            'form.estimated_arrival' => 'nullable|date',
            'form.lane_status' => 'nullable|string']);

        if (empty($this->form['estimated_arrival'])) $this->form['estimated_arrival'] = null;
        if (empty($this->form['lane_status'])) $this->form['lane_status'] = null;

        // Auto Generate AWB
        if (empty($this->form['awb_number'])) {
            $prefix = match($this->form['service_type']) {
                'import' => 'IMP', 'export' => 'EXP', 'domestic' => 'DOM', default => 'JOB'
            };
            $this->form['awb_number'] = $prefix . '-' . date('ymd') . '-' . rand(100,999);
        }

        // AUTO STATUS LOGIC
        if ($this->mark_as_completed) {
            $this->form['status'] = 'completed';
        } elseif (!empty($this->form['lane_status'])) {
            $this->form['status'] = 'in_progress';
        } else {
             if (!$this->isEditing) $this->form['status'] = 'pending';
        }

        if ($this->isEditing) {
            $shipment = Shipment::find($this->editingId);
            $oldStatus = $shipment->status;
            $this->form['volume'] = $this->form['volume'] !== '' ? $this->form['volume'] : null;
            $shipment->update($this->form);

            if ($oldStatus !== $this->form['status']) {
                ActivityLog::record('Shipment', 'UPDATE STATUS', $shipment->awb_number, "Status berubah ke '{$this->form['status']}'");
                $shipment->load('customer.user');
                if ($shipment->customer && $shipment->customer->user) {
                    try { Mail::to($shipment->customer->user->email)->send(new ShipmentStatusUpdated($shipment)); \App\Models\SentEmail::create(['mailbox' => 'no_reply', 'to_email' => $shipment->customer->user->email, 'subject' => 'Status Update: ' . $shipment->awb_number, 'body' => 'Status shipment diupdate ke ' . $shipment->status, 'user_id' => auth()->id(), 'user_name' => auth()->user()->name]); } catch (\Exception $e) {}
                }
            } else {
                ActivityLog::record('Shipment', 'UPDATE INFO', $shipment->awb_number, "Mengubah detail data shipment.");
            }
            $message = 'Shipment Updated Successfully.';
        } else {
            $this->form['volume'] = $this->form['volume'] !== '' ? $this->form['volume'] : null;
            $shipment = Shipment::create($this->form);
            ActivityLog::record('Shipment', 'CREATE', $shipment->awb_number, "Membuat shipment baru.");
            $message = 'Shipment Created Successfully.';
        }

        $this->closeModal();
        session()->flash('message', $message);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    public function deleteShipment($id = null)
    {
        $targetId = $id ?? $this->deleteId;
        
        if (!in_array(Auth::user()->role, ['admin', 'superadmin', 'director'])) {
            session()->flash('error', 'Access Denied');
            return;
        }
        
        $shipment = Shipment::find($targetId);
        if ($shipment) {
            foreach ($shipment->documents as $doc) {
                if (Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
                $doc->delete();
            }
            ActivityLog::record('Shipment', 'DELETE', $shipment->awb_number, "Hapus data.");
            $shipment->delete();
            session()->flash('message', 'Shipment deleted successfully.');
        }
        
        $this->cancelDelete();
    }
    
    public function uploadInternalEvidence()
    {
        $this->validate([
            'internal_photo' => 'required|image|max:10240',
            'internal_note' => 'required|string|max:255'
        ]);
        
        try {
            $filename = 'INTERNAL_' . time() . '_' . rand(100, 999) . '.webp';
            $path = 'documents/internal_evidence/' . $filename;
            $manager = new ImageManager(new Driver());
            $image = $manager->read($this->internal_photo->getRealPath());
            $image->scale(width: 1000);
            $encoded = $image->toWebp(70);
            Storage::disk('public')->put($path, (string)$encoded);
            
            Document::create([
                'shipment_id' => $this->uploadingShipmentId,
                'document_type' => 'internal_photo',
                'filename' => $filename,
                'file_path' => $path,
                'description' => $this->internal_note,
                'is_internal' => true,
                'uploaded_by' => Auth::id(),
                'file_size' => strlen((string)$encoded),
                'mime_type' => 'image/webp',
                'uploaded_at' => now()
            ]);
            
            $this->reset(['internal_photo', 'internal_note', 'uploadingShipmentId']);
            session()->flash('message', 'Foto Internal berhasil disimpan.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

    // === MODAL CETAK SURAT JALAN ===
    public function openPrintDoModal($shipmentId)
    {
        $this->printDoShipmentId = $shipmentId;
        $this->printDoShipment = Shipment::with('customer')->find($shipmentId);
        $this->printDoContainerCount = 1;
        $this->printDoContainers = [
            ['no_container' => '', 'no_polisi' => '', 'nama_supir' => '', 'no_seal' => '']
        ];

        $shipment = $this->printDoShipment;

        // Jika ada data DO tersimpan sebelumnya, gunakan itu
        if (!empty($shipment->do_containers)) {
            $this->printDoContainers = $shipment->do_containers;
            $this->printDoContainerCount = count($shipment->do_containers);
        }

        if (!empty($shipment->do_alamat_bongkar)) {
            $this->printDoAlamatBongkar = $shipment->do_alamat_bongkar;
        } else {
            // Pre-fill dari data customer
            $customer = $shipment?->customer;
            if ($customer) {
                $parts = array_filter([
                    $customer->address,
                    $customer->city,
                    $customer->postal_code,
                    $customer->country ?? 'Indonesia',
                ]);
                $this->printDoAlamatBongkar = implode(', ', $parts);
            }
            if (empty($this->printDoAlamatBongkar)) {
                $this->printDoAlamatBongkar = $shipment?->destination ?? '';
            }
        }

        $this->printDoNamaPenerima = $shipment->do_nama_penerima ?? '';

        $this->showPrintDoModal = true;
    }

    public function updatedPrintDoContainerCount($value)
    {
        $count = max(1, min(100, (int)$value));
        $current = count($this->printDoContainers);
        
        if ($count > $current) {
            for ($i = $current; $i < $count; $i++) {
                $this->printDoContainers[] = ['no_container' => '', 'no_polisi' => '', 'nama_supir' => '', 'no_seal' => ''];
            }
        } elseif ($count < $current) {
            $this->printDoContainers = array_slice($this->printDoContainers, 0, $count);
        }
        
        $this->printDoContainerCount = $count;
    }

    public function closePrintDoModal()
    {
        $this->showPrintDoModal = false;
        $this->printDoShipmentId = null;
        $this->printDoShipment = null;
        $this->printDoContainers = [];
        $this->printDoContainerCount = 1;
        $this->printDoAlamatBongkar = '';
        $this->printDoNamaPenerima = '';
    }

    public function printDo()
    {
        // Simpan data DO ke shipment supaya bisa di-recall berikutnya
        Shipment::where('id', $this->printDoShipmentId)->update([
            'do_containers'    => $this->printDoContainers,
            'do_alamat_bongkar' => $this->printDoAlamatBongkar,
            'do_nama_penerima'  => $this->printDoNamaPenerima,
        ]);

        $containers = urlencode(json_encode($this->printDoContainers));
        $alamatBongkar = urlencode($this->printDoAlamatBongkar);
        $namaPenerima = urlencode($this->printDoNamaPenerima);
        $url = route('admin.shipments.print-do', ['id' => $this->printDoShipmentId])
            . '?containers=' . $containers
            . '&alamat_bongkar=' . $alamatBongkar
            . '&nama_penerima=' . $namaPenerima;
        $this->dispatch('openPrintWindow', url: $url);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }
    
    private function resetForm()
    {
        $this->reset('form', 'editingId', 'mark_as_completed');
        $this->form['service_type'] = 'import';
        $this->form['shipment_type'] = 'sea';
        $this->form['status'] = 'pending';
        $this->form['container_mode'] = 'LCL';
        $this->form['package_type'] = 'Colli';
        $this->form['lane_status'] = '';
    }
    public function viewCancelReason($id)
    {
        $this->cancelReasonShipment = Shipment::with("cancelledBy")->find($id);
        $this->showCancelReasonModal = true;
    }

    public function closeCancelReasonModal()
    {
        $this->showCancelReasonModal = false;
        $this->cancelReasonShipment = null;
    }
}
