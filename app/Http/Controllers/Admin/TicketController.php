<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminBaseController;
use App\Ticket;
use Illuminate\Http\Request;
use App\User;
use App\TicketType;
use App\Helper\Files;
use App\TicketPriority;
use Froiden\Envato\Helpers\Reply;
use App\Http\Requests\Admin\Ticket\Store;
use App\Http\Requests\TicketReply\Store as ReplyStore;
use App\TicketComment;
use App\TicketTemplate;

class TicketController extends AdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        view()->share('pageTitle', __('app.ticket'));
    }

    public function index()
    {
         abort_403(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission(['read_ticket','create_ticket', 'update_ticket', 'delete_ticket']));

        if (\request()->ajax()) {
            $ticket = Ticket::with(['type', 'priority'])->vendor(company()->id)->get();

            return \datatables()->of($ticket)
                ->addColumn('action', function ($row) {
                    $action = '<div class="text-right">';

                    if ($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_ticket')) {
                        $action .= '<a href="' . route('admin.tickets.edit', [$row->id]) . '" class="btn btn-primary btn-circle" data-toggle="tooltip" data-original-title="' . __('app.edit') . '"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                    }

                    if ($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('delete_ticket') && ($row->status != 'closed')) {
                        $action .= ' <a href="javascript:;" class="btn btn-danger btn-circle delete-row" data-toggle="tooltip" data-row-id="' . $row->id . '" data-original-title="' . __('app.close') . '"><i class="fa fa-times" aria-hidden="true"></i></a>';
                    }

                    $action .= '</div>';

                    return $action;
                })
                ->editColumn('subject', function ($row) {
                    return '<a href="' . route('admin.tickets.edit', [$row->id]) . '" data-toggle="tooltip" data-original-title="' . __('app.reply') . '">'.ucwords($row->subject).'</a>';
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == 'open') {
                        return '<span class="badge badge-danger">' . __('app.open') . '</span>';
                    }
                    elseif ($row->status == 'pending') {
                        return '<label class="badge badge-warning">' . __('app.pending') . '</label>';
                    }
                    elseif ($row->status == 'resolved') {
                        return '<label class="badge badge-info">' . __('app.resolved') . '</label>';
                    }
                    elseif ($row->status == 'closed') {
                        return '<label class="badge badge-success">' . __('app.closed') . '</label>';
                    }
                })
                ->addIndexColumn()
                ->rawColumns(['subject', 'action', 'status'])
                ->toJson();
        }

        return view('admin.ticket.index');
    }

    public function create()
    {
        abort_403(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_ticket'));

        $this->agents = User::allAgents()->get();
        $this->types = TicketType::get();
        $this->priorities = TicketPriority::get();
        $this->ticketTemplate = TicketTemplate::get();

        return view('admin.ticket.create', $this->data);
    }

    public function store(Store $request)
    {
        abort_403(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_ticket'));

        $ticket = new Ticket();
        $ticket->vendor_id = company()->id;
        $ticket->type_id = $request->type;
        $ticket->priority_id = $request->priority;
        $ticket->subject = $request->subject;
        $ticket->status = 'open';
        $ticket->save();

        $ticketComment = new TicketComment();
        $ticketComment->user_id = $this->user->id;
        $ticketComment->ticket_id = $ticket->id;
        $ticketComment->comment = $request->message;
        $ticketComment->save();

        return Reply::successWithData(__('messages.ticketReplySuccess'), ['ticketReplyID' => $ticketComment->id]);

    }

    public function replyStore(ReplyStore $request)
    {
        $ticket = Ticket::findOrFail($request->ticket_id);
        $ticket->status = 'open';
        $ticket->update();

        $reply = new TicketComment();
        $reply->user_id = $this->user->id;
        $reply->ticket_id = $ticket->id;
        $reply->comment = $request->message;
        $reply->save();

        return Reply::dataOnly(['ticketReplyID' => $reply->id]);
    }

    public function latsReplyStore($id)
    {
        $reply = TicketComment::with('ticket')->findOrFail($id);
        $ticket = $reply->ticket;
        $lastMessage = view('admin.ticket.last-message', compact('reply', 'ticket'))->render();

        return Reply::successWithData(__('messages.ticketReplySuccess'), ['lastMessage' => $lastMessage]);
    }

    public function edit($id)
    {
        abort_403(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_ticket'));

        $this->ticket = Ticket::with('reply')->findOrFail($id);
        $this->agents = User::allAgents()->get();
        $this->types = TicketType::get();
        $this->priorities = TicketPriority::get();
        $this->ticketTemplate = TicketTemplate::get();

        return view('admin.ticket.edit', $this->data);
    }

    public function update(Request $request, Ticket $ticket)
    {
        abort_403(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_ticket'));

        $ticket->agent_id = $request->agent;
        $ticket->type_id = $request->type;
        $ticket->priority_id = $request->priority;
        $ticket->update();

        return Reply::redirect(route('admin.tickets.index'), __('messages.updatedSuccessfully'));
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->status = 'closed';
        $ticket->update();
        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function deleteReply($id)
    {
        $ticketComment = TicketComment::findOrFail($id);
        $ticketComment->delete();
        return Reply::success(__('messages.recordDeleted'));
    }

    public function storeImages(Request $request)
    {
        if ($request->hasFile('file')) {
            $ticketComment = TicketComment::with('ticket')->where('id', $request->ticketReplyID)->first();

            if ($ticketComment) {
                $ticket_files_arr = [];

                foreach ($request->file as $fileData) {
                    array_push($ticket_files_arr, Files::uploadLocalOrS3($fileData, 'ticket/'.$ticketComment->ticket->id));
                }

                $ticketComment->files = json_encode($ticket_files_arr);
                $ticketComment->save();
            }
        }

        return Reply::redirect(route('admin.tickets.index'), __('messages.createdSuccessfully'));
    }

    public function fetchTemplate(Request $request)
    {
        $ticketTemplate = TicketTemplate::findOrFail($request->templateId);

        return Reply::dataOnly(['replyText' => $ticketTemplate->message]);
    }

}
