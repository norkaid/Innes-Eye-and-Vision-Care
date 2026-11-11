<?php

namespace App\Services;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\User;
use App\Notifications\EnquiryReceived;
use App\Notifications\StaffNewEnquiry;

class EnquiryService
{
    public function __construct(private NotificationService $notifications, private AuditLogger $audit) {}

    /**
     * @param  array<string,mixed>  $data
     * @return array{enquiry: Enquiry, acknowledged: bool}
     */
    public function submit(array $data, ?User $user = null, ?int $productId = null): array
    {
        $enquiry = new Enquiry($data);
        $enquiry->status = EnquiryStatus::New;
        $enquiry->product_id = $productId;
        if ($user && $user->isCustomer()) {
            $enquiry->user_id = $user->id;
        }
        $enquiry->save();

        $acknowledged = $this->notifications->toCustomer($enquiry->email, new EnquiryReceived($enquiry), $enquiry);
        $this->notifications->toStaff(new StaffNewEnquiry($enquiry), $enquiry);

        return ['enquiry' => $enquiry, 'acknowledged' => $acknowledged];
    }

    /** @param array{status:string, assigned_to:?int} $data */
    public function update(Enquiry $enquiry, array $data): void
    {
        $before = ['status' => $enquiry->status->value, 'assigned_to' => $enquiry->assigned_to];
        $enquiry->status = EnquiryStatus::from($data['status']);
        $enquiry->assigned_to = $data['assigned_to'] ?? null;
        $enquiry->save();
        $this->audit->log('enquiry.updated', $enquiry, ['from' => $before, 'to' => ['status' => $enquiry->status->value, 'assigned_to' => $enquiry->assigned_to]]);
    }

    public function addNote(Enquiry $enquiry, string $note, User $staff): void
    {
        $model = $enquiry->notes()->make(['note' => $note]);
        $model->user_id = $staff->id;
        $model->save();
        $this->audit->log('enquiry.note_added', $enquiry);
    }
}
