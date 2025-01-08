<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Messages\Entities\MessageWithMail;
use App\Traits\HttpResponses;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Modules\Messages\Http\Requests\UpdateLetterRequest;
use DB;
use Log;


class MessageWithLetterController extends Controller
{
    use HttpResponses;

    public function sentList(Request $request)
    {
        try {
            // Retrieve search, sort_field, sort_value, sizePerPage, page, offset, and limit parameters from the request
            $search = $request->input('search');
            $sortField = $request->input('sortField', 'message_with_mails.created_at'); // Default to 'created_at' if not provided
            $sortValue = $request->input('sortValue', 'desc');  // Default to 'desc' if not provided

            $sizePerPage = $request->input('perPage', 10); // Default to 15 items per page if not provided
            $page = $request->input('page', 1); // Default to the first page if not provided
            $offset = $request->input('offset', 0); // Default to 0 if not provided
            $limit = $request->input('limit', $sizePerPage); // Default to sizePerPage if not provided

            $user = auth('api')->user(); // Retrieve logged-in user
            $company_id = $user->company_id; // Get the company_id
            $user_email = $user->email; // Get the logged-in user's email
            $user_type = $user->user_type; // Get the logged-in user's type

            // Start building the query for retrieving sent letters
            $lettersQuery = MessageWithMail::leftJoin('contacts as recipients', 'message_with_mails.to', '=', 'recipients.id')
                ->join('users as senders', 'message_with_mails.from', '=', 'senders.email')
                ->where('message_with_mails.company_id', $company_id)
                ->where('message_with_mails.type', 'letter')
                ->where('message_with_mails.status', 'Sent')
                ->select(
                    'message_with_mails.id',
                    DB::raw("CONCAT(recipients.first_name, ' ', recipients.last_name) as recipient_full_name"),
                    DB::raw("CONCAT(senders.first_name, ' ', senders.last_name) as sender_full_name"),
                    'message_with_mails.subject',
                    'message_with_mails.body',
                    'message_with_mails.status',
                    'message_with_mails.created_at',
                );

            $contactId = null;
            if (in_array($user_type, ['Owner', 'Tenant'])) {
                $contact = DB::table('contacts')
                    ->where('email', $user_email)
                    ->first();

                // If the contact exists, retrieve the contact ID
                if ($contact) {
                    $contactId = $contact->id;
                }
            }

            // If user_type is Owner or Tenant, filter the message_with_mails.to by contact id
            if ($contactId) {
                $lettersQuery->where('message_with_mails.to', '=', $contactId);
            }

            // Apply search filter if provided
            if (!empty($search) && $search != 'null') {
                $lettersQuery->where(function ($query) use ($search) {
                    $query->where(DB::raw("CONCAT(recipients.first_name, ' ', recipients.last_name)"), 'like', '%' . $search . '%')
                        ->orWhere(DB::raw("CONCAT(senders.first_name, ' ', senders.last_name)"), 'like', '%' . $search . '%')
                        ->orWhere('message_with_mails.subject', 'like', '%' . $search . '%')
                        ->orWhere('message_with_mails.status', 'like', '%' . $search . '%');
                });
            }

            // Apply sorting based on sort_field and sort_value
            $lettersQuery->orderBy($sortField, $sortValue);

            // Calculate the offset and limit for pagination
            $offset = ($page - 1) * $sizePerPage;
            $limit = $sizePerPage;

            // Apply the limit and offset
            $lettersQuery->skip($offset)->take($limit);

            // Execute the query and get the results
            $letters = $lettersQuery->get();

            // Get the total count for pagination info
            $totalCount = MessageWithMail::where('message_with_mails.company_id', auth('api')->user()->company_id)
                ->where('message_with_mails.type', 'letter')
                ->where('message_with_mails.status', 'Sent')
                ->count();

            // Calculate total pages
            $totalPages = ceil($totalCount / $sizePerPage);

            // Execute the query and get the results
            $letters = $lettersQuery->get();

            return $this->success([
                'data' => $letters,
                'total' => $totalCount,
                'perPage' => $sizePerPage,
                'currentPage' => $page,
                'totalPages' => $totalPages,
            ], 'Sent Letters Retrieved Successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }


    public function outboxList(Request $request)
    {
        try {
            // Retrieve search, sort_field, sort_value, sizePerPage, page, offset, and limit parameters from the request
            $search = $request->input('search');
            $sortField = $request->input('sortField', 'message_with_mails.created_at'); // Default to 'created_at' if not provided
            $sortValue = $request->input('sortValue', 'desc');  // Default to 'desc' if not provided

            $sizePerPage = $request->input('perPage', 10); // Default to 15 items per page if not provided
            $page = $request->input('page', 1); // Default to the first page if not provided
            $offset = $request->input('offset', 0); // Default to 0 if not provided
            $limit = $request->input('limit', $sizePerPage); // Default to sizePerPage if not provided

            // Start building the query for retrieving outbox letters
            $lettersQuery = MessageWithMail::leftJoin('contacts as recipients', 'message_with_mails.to', '=', 'recipients.id')
                ->join('users as senders', 'message_with_mails.from', '=', 'senders.email')
                ->where('message_with_mails.company_id', auth('api')->user()->company_id)
                ->where('message_with_mails.type', 'letter')
                ->where('message_with_mails.status', 'Outbox')
                ->select(
                    'message_with_mails.id',
                    DB::raw("CONCAT(recipients.first_name, ' ', recipients.last_name) as recipient_full_name"),
                    DB::raw("CONCAT(senders.first_name, ' ', senders.last_name) as sender_full_name"),
                    'message_with_mails.subject',
                    'message_with_mails.body',
                    'message_with_mails.status',
                    'message_with_mails.created_at',
                );

            // Apply search filter if provided
            if (!empty($search) && $search != 'null') {
                $lettersQuery->where(function ($query) use ($search) {
                    $query->where(DB::raw("CONCAT(recipients.first_name, ' ', recipients.last_name)"), 'like', '%' . $search . '%')
                        ->orWhere(DB::raw("CONCAT(senders.first_name, ' ', senders.last_name)"), 'like', '%' . $search . '%')
                        ->orWhere('message_with_mails.subject', 'like', '%' . $search . '%')
                        ->orWhere('message_with_mails.status', 'like', '%' . $search . '%');
                });
            }

            // Apply sorting based on sort_field and sort_value
            $lettersQuery->orderBy($sortField, $sortValue);

            // Calculate the offset and limit for pagination
            $offset = ($page - 1) * $sizePerPage;
            $limit = $sizePerPage;

            // Apply the limit and offset
            $lettersQuery->skip($offset)->take($limit);

            // Execute the query and get the results
            $letters = $lettersQuery->get();

            // Get the total count for pagination info
            $totalCount = MessageWithMail::where('message_with_mails.company_id', auth('api')->user()->company_id)
                ->where('message_with_mails.type', 'letter')
                ->where('message_with_mails.status', 'Outbox')
                ->count();

            // Calculate total pages
            $totalPages = ceil($totalCount / $sizePerPage);

            // Execute the query and get the results
            $letters = $lettersQuery->get();

            return $this->success([
                'data' => $letters,
                'total' => $totalCount,
                'perPage' => $sizePerPage,
                'currentPage' => $page,
                'totalPages' => $totalPages,
            ], 'Outbox Letters Retrieved Successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }


    public function details($id)
    {
        try {
            $letter = MessageWithMail::with('property', 'contacts', 'job', 'inspection', 'task', 'mailAttachment.attachemnt')
                ->where('id', $id)
                ->first();

            // Check if the letter template exists
            if (!$letter) {
                return $this->error('Letter not found.', null, 404);
            }

            return $this->success($letter, 'Letter Retrieved Successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function download($id)
    {
        try {
            $letter = MessageWithMail::find($id);

            if (!$letter) {
                return $this->error('Letter not found.', null, 404);
            }

            // Load the Blade template and pass data
            $pdf = PDF::loadView('messages::letterPdf', ['letter' => $letter]);

            // Return the PDF as a download
            return $pdf->download('letter-' . $letter->id . '.pdf');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function print($id)
    {
        // Logic for printing a specific message
        // Implementation to be added
    }

    public function update($id, UpdateLetterRequest $request)
    {
        try {
            $letter = MessageWithMail::find($id);

            if (!$letter) {
                return $this->error('Letter not found.', null, 404);
            }

            // Update the MailTemplate
            $letter->body = $request->message;
            $letter->save();

            return $this->success($letter, 'Letter updated successfully.');
        } catch (\Throwable $th) {
            // Handle errors
            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function deleteMultiple(Request $request)
    {
        try {
            // Get the array of IDs from the request
            $ids = $request['id'];

            // Retrieve the IDs of the records that are about to be deleted
            $deletedIds = MessageWithMail::whereIn('id', $ids)->pluck('id');

            // Delete all the records with the given IDs
            MessageWithMail::whereIn('id', $ids)->delete();

            return $this->success($deletedIds, 'Letter Deleted Successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function sendMultiple(Request $request)
    {
        try {
            // Get the array of IDs from the request
            $ids = $request['id'];

            // Retrieve the IDs of the records that are about to be updated
            $updatedIds = MessageWithMail::whereIn('id', $ids)->pluck('id');

            // Update the status of all records with the given IDs to "Sent"
            MessageWithMail::whereIn('id', $ids)->update(['status' => 'Sent']);

            return $this->success($updatedIds, 'Status updated to Sent successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }
}
