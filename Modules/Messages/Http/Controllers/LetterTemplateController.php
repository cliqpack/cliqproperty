<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerPoint;
use Modules\Messages\Entities\MessageActionTriggerTo;
use Modules\Messages\Entities\MailTemplate;
use App\Traits\HttpResponses;
use Modules\Messages\Http\Requests\StoreLetterTemplateRequest;
use Modules\Messages\Http\Requests\UpdateLetterTemplateRequest;

class LetterTemplateController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            // Retrieve search, sort_field, sort_value, sizePerPage, page, offset, and limit parameters from the request
            $search = $request->input('search');
            $sortField = $request->input('sortField', 'name'); // Default to 'name' if not provided
            $sortValue = $request->input('sortValue', 'asc');  // Default to 'asc' if not provided

            $sizePerPage = $request->input('perPage', 10); // Default to 15 items per page if not provided
            $page = $request->input('page', 1); // Default to the first page if not provided
            $offset = $request->input('offset', 0); // Default to 0 if not provided
            $limit = $request->input('limit', $sizePerPage); // Default to sizePerPage if not provided


            // Fetch letter templates for the authenticated user's company
            $letterTemplatesQuery = MailTemplate::select('id', 'name', 'message_action_name', 'message_trigger_to', 'messsage_trigger_point', 'body', 'status', 'type')
                ->where('company_id', auth('api')->user()->company_id)
                ->where('type', 'letter');

            // Apply the search filter only if the search term is not null or empty
            if ($search != 'null') {
                $letterTemplatesQuery->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('message_action_name', 'like', '%' . $search . '%')
                        ->orWhere('message_trigger_to', 'like', '%' . $search . '%')
                        ->orWhere('messsage_trigger_point', 'like', '%' . $search . '%');
                });
            }

            // Apply sorting based on sort_field and sort_value
            $letterTemplatesQuery->orderBy($sortField, $sortValue);

            // Calculate the offset and limit for pagination
            $offset = ($page - 1) * $sizePerPage;
            $limit = $sizePerPage;

            // Apply the limit and offset
            $letterTemplatesQuery->skip($offset)->take($limit);

            // Execute the query and get the results
            $letterTemplates = $letterTemplatesQuery->get();

            // Get the total count for pagination info
            $totalCount = MailTemplate::where('company_id', auth('api')->user()->company_id)
                ->where('type', 'letter')
                ->count();

            // Calculate total pages
            $totalPages = ceil($totalCount / $sizePerPage);

            // Execute the query and get the results
            $letterTemplates = $letterTemplatesQuery->get();

            return $this->success([
                'data' => $letterTemplates,
                'total' => $totalCount,
                'perPage' => $sizePerPage,
                'currentPage' => $page,
                'totalPages' => $totalPages,
            ], 'Letter Templates Retrieved Successfully.');

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }



    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(StoreLetterTemplateRequest $request)
    {
        try {

            // Fetch related entities
            $message_action_name = MessageActionName::select('name')
                ->where('id', $request->message_action_name_id)
                ->first();
            $message_trigger_to = MessageActionTriggerTo::select('trigger_to')
                ->where('id', $request->message_trigger_to_id)
                ->first();
            $messsage_trigger_point = MessageActionTriggerPoint::select('trigger_point')
                ->where('id', $request->messsage_trigger_point_id)
                ->first();

            // Create new MailTemplate instance
            $letterTemplate = new MailTemplate();
            $letterTemplate->name = $request->name;
            $letterTemplate->body = $request->message;
            $letterTemplate->subject = $request->name;
            $letterTemplate->company_id = auth('api')->user()->company_id;
            $letterTemplate->message_action_name = $message_action_name->name;
            $letterTemplate->message_trigger_to = $message_trigger_to->trigger_to;
            $letterTemplate->messsage_trigger_point = $messsage_trigger_point->trigger_point;
            $letterTemplate->action_name_id = $request->message_action_name_id;
            $letterTemplate->trigger_to_id = $request->message_trigger_to_id;
            $letterTemplate->trigger_point_id = $request->messsage_trigger_point_id;
            $letterTemplate->status = $request->status;
            $letterTemplate->type = "letter";
            $letterTemplate->email_sends_automatically = $request->email_sends_automatically;
            $letterTemplate->save();

            return $this->success($letterTemplate->id, 'Letter Template Added Successfully.');

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {

            // Retrieve a single letter template record by its ID
            $letterTemplate = MailTemplate::select('id', 'name', 'body', 'message_action_name', 'message_trigger_to', 'messsage_trigger_point', 'action_name_id', 'trigger_to_id', 'trigger_point_id', 'status', 'type')
                ->where('id', $id)->first();

            // Check if the letter template exists
            if (!$letterTemplate) {
                return $this->error('Letter Template not found.', null, 404);
            }

            return $this->success($letterTemplate, 'Letter Template Retrieved Successfully.');

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(UpdateLetterTemplateRequest $request, $id)
    {
        try {

            // Retrieve the existing letter template
            $letterTemplate = MailTemplate::find($id);

            // Check if the letter template exists
            if (!$letterTemplate) {
                return $this->error('Letter Template not found.', null, 404);
            }

            // Update the MailTemplate
            $letterTemplate->body = $request->message;
            $letterTemplate->status = $request->status;
            $letterTemplate->save();

            return $this->success($letterTemplate->id, 'Letter Template Updated Successfully.');

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function destroyMultiple(Request $request)
    {
        try {
            // Get the array of IDs from the request
            $ids = $request['id'];

            // Retrieve the IDs of the records that are about to be deleted
            $deletedIds = MailTemplate::whereIn('id', $ids)->pluck('id');

            // Delete all the records with the given IDs
            MailTemplate::whereIn('id', $ids)->delete();

            return $this->success($deletedIds, 'Letter Template Deleted Successfully.');

        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }
}
