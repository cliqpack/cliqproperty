<?php

namespace App\Http\Controllers\Imap;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contact;
use Webklex\PHPIMAP\ClientManager;
use App\Models\Imap\ImapModel;
use App\Models\Imap\ThreadImapModel;
use Illuminate\Support\Facades\Storage;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Messages\Entities\MessageWithMailReply;
use Modules\Settings\Entities\MessagePortfolioEmailSetting;
use Log;

class ImapController extends Controller
{
    protected $email = '';
    protected $companyId = 0;

    public function __construct()
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);
    }

    public function index()
    {
        $mailsettings = MessagePortfolioEmailSetting::all();
        if ($mailsettings->count() > 0) {

            $invalidEmails = [];
            $validEmails = [];
            $fetchedIncomingErrors = [];

            foreach ($mailsettings as $mailsetting) {
                if ($mailsetting) {

                    $getEmail = $mailsetting->portfolio_email . "@myday.biz";
                    $getCompanyId = $mailsetting->company_id;

                    if ($getEmail) {
                        $validEmailDatas = [];
                        $invalidEmailDatas = [];
                        $fetchedIncomingErrorDatas = [];
                        try {
                            config(['imap.accounts.default.username' => $getEmail]);
                            $cm = new ClientManager(config('imap'));
                            $client = $cm->account('default');
                            $client->connect();

                            $folder = $client->getFolder('INBOX');
                            $query = $folder->query();
                            $messages = $query->since(now()->subDays(1))->get();
                            $validEmailDatas['connection e-mail: '] = $getEmail;

                            $client->disconnect();
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                            $errorCode = $e->getCode();
                            $errorString = $e->__toString();

                            $invalidEmailDatas['connection email failed'] = $getEmail;
                            $invalidEmailDatas['connection status'] = 'failed...';
                            $invalidEmailDatas['connection code'] = $errorCode;
                            $invalidEmailDatas['connection message'] = $errorMessage;
                            array_push($invalidEmails, $invalidEmailDatas);
                            continue;
                        }

                        // Checklist of Re: and exclued emails from mail server
                        $cnt = 0;
                        $cntThread = 0;
                        $replyCheckList = "Re:"; // Require added in array if necessery
                        $fromMailCheckList = [
                            "My day <" . config('imap.accounts.default.username') . ">",
                            "cliqpack <" . config('imap.accounts.default.username') . ">",
                            "dexlan <" . config('imap.accounts.default.username') . ">",
                            "toho <" . config('imap.accounts.default.username') . ">",
                            "moment <" . config('imap.accounts.default.username') . ">",
                            'Mail Delivery Subsystem <mailer-daemon@googlemail.com>',
                            'Mail Delivery System <MAILER-DAEMON@smtp-out.titan.email>',
                        ];
                        // Arrys of mail messages obj that inserted to imap relate tables
                        $insertedObjCollections = [];
                        $insertedThreadObjCollections = [];

                        // All mail messages, master mail messages and Re: mail messages collections
                        $messageCollections = collect([]);
                        $messageNoReCollections = collect([]);
                        $messageReCollections = collect([]);

                        // Distinguishing started of all fetched mails
                        try {
                            // Separating all mail messages, master mail messages and Re: mail messages collections
                            // Re: type mail messages for thread collections
                            foreach ($messages as $message) {
                                if (!in_array($message->from, $fromMailCheckList)) {

                                    $currIncomingMail = $this->getMailMessages($message, $getCompanyId);
                                    $messageCollections->push($currIncomingMail);
                                    if (str_contains($message->subject, $replyCheckList) == false) {

                                        $currIncomingMail = $this->getMailMessages($message, $getCompanyId);
                                        $messageNoReCollections->push($currIncomingMail);
                                    } else {

                                        $currIncomingMail = $this->getMailMessages($message, $getCompanyId);
                                        $messageReCollections->push($currIncomingMail);
                                    }
                                }
                            }

                            // Reversing all mail messages, master mail messages and Re: mail messages collections
                            // to show recent mails messages to top in dB
                            $messageCollections = $messageCollections->reverse();
                            $messageNoReCollections = $messageNoReCollections->reverse();
                            $messageReCollections = $messageReCollections->reverse();

                            //Master mail messages data insertion to dB
                            foreach ($messageNoReCollections as $messageCollection) {
                                $toMessages = collect([]);
                                if ($messageCollection['To']->count() > 0) {
                                    for ($i = 0; $i < $messageCollection['To']->count(); $i++) {
                                        $mailAddresses = $messageCollection['To'][$i]->mail;
                                        if (isset($mailAddresses)) {
                                            // array_push($ccMessages, $mailAddresses);
                                            $toMessages->push($mailAddresses);
                                        }
                                    }
                                }

                                $ccMessages = collect([]);
                                if ($messageCollection['Cc']->count() > 0) {
                                    for ($i = 0; $i < $messageCollection['Cc']->count(); $i++) {
                                        $mailAddresses = $messageCollection['Cc'][$i]->mail;
                                        if (isset($mailAddresses)) {
                                            // array_push($ccMessages, $mailAddresses);
                                            $ccMessages->push($mailAddresses);
                                        }
                                    }
                                }

                                $bccMessages = collect([]);
                                if ($messageCollection['Bcc']->count() > 0) {
                                    for ($i = 0; $i < $messageCollection['Bcc']->count(); $i++) {
                                        $mailAddresses = $messageCollection['Bcc'][$i]->mail;
                                        if (isset($mailAddresses)) {
                                            // array_push($bccMessages, $mailAddresses);
                                            $bccMessages->push($mailAddresses);
                                        }
                                    }
                                }

                                try {
                                    $imapModel = ImapModel::firstOrCreate(
                                        [
                                            'subject' => $messageCollection['Subject'],
                                            'from' => $messageCollection['From'],
                                            'to' => $toMessages->all(),
                                        ],
                                        [
                                            'message_id' => $messageCollection['messageId'],
                                            'message_uid' => $messageCollection['messageUid'],
                                            'message_no' => $messageCollection['messageNo'],
                                            'in_reply_to_id' => $messageCollection['InReplyToId'],
                                            'reply_to_id' => $messageCollection['replyToId'],
                                            'cc' => $ccMessages,
                                            'bcc' => $bccMessages,
                                            'date' => $messageCollection['Date'],
                                            'body' => $messageCollection['Body'],
                                        ]
                                    );

                                    if ($imapModel->wasRecentlyCreated) {
                                        $cnt++;
                                        array_push(
                                            $insertedObjCollections,
                                            $this->insertedObjCollectionsMethod($imapModel, $messageCollection, $toMessages, $ccMessages, $bccMessages)
                                        );
                                    } else {
                                        array_push(
                                            $insertedObjCollections,
                                            "No inbound mail found"
                                        );
                                    }
                                } catch (\Exception $e) {
                                    $errorMessage = $e->getMessage();
                                    $errorCode = $e->getCode();
                                    $errorString = $e->__toString();

                                    array_push(
                                        $insertedObjCollections,
                                        [
                                            'General message' => 'Inbound mail problem',
                                            'Message code' => $errorCode,
                                            'Message info' => $errorMessage,
                                        ]
                                    );
                                }
                            }

                            // Thread mail messages data insertion to dB
                            foreach ($messageCollections as $messageCollection) {
                                $toMessages = collect([]);
                                if ($messageCollection['To']->count() > 0) {
                                    for ($i = 0; $i < $messageCollection['To']->count(); $i++) {
                                        $mailAddresses = $messageCollection['To'][$i]->mail;
                                        if (isset($mailAddresses)) {
                                            $toMessages->push($mailAddresses);
                                        }
                                    }
                                }

                                $ccMessages = collect([]);
                                if ($messageCollection['Cc']->count() > 0) {
                                    for ($i = 0; $i < $messageCollection['Cc']->count(); $i++) {
                                        $mailAddresses = $messageCollection['Cc'][$i]->mail;
                                        if (isset($mailAddresses)) {
                                            $ccMessages->push($mailAddresses);
                                        }
                                    }
                                }

                                $bccMessages = collect([]);
                                if ($messageCollection['Bcc']->count() > 0) {
                                    for ($i = 0; $i < $messageCollection['Bcc']->count(); $i++) {
                                        $mailAddresses = $messageCollection['Bcc'][$i]->mail;
                                        if (isset($mailAddresses)) {
                                            $bccMessages->push($mailAddresses);
                                        }
                                    }
                                }

                                if (str_contains($messageCollection['Subject'], $replyCheckList) == true) {
                                    $subject1 = $messageCollection['Subject'];
                                    $subject = substr($messageCollection['Subject'], 4);
                                } else {
                                    $subject1 = $messageCollection['Subject'];
                                    $subject = $messageCollection['Subject'];
                                }

                                $imapModel = ImapModel::where('from', $messageCollection['From'])
                                    ->where('subject', $subject)
                                    ->whereJsonContains('to', $toMessages->all())
                                    ->first();


                                if (isset($imapModel) && $imapModel->count() > 0) {
                                    if ($imapModel->message_uid != $messageCollection['messageUid']) {
                                        try {
                                            $threadImapModel = ThreadImapModel::firstOrCreate(
                                                [
                                                    'message_id' => $messageCollection['messageId'],
                                                ],
                                                [
                                                    'imap_model_id' => $imapModel->id,
                                                    'message_uid' => $messageCollection['messageUid'],
                                                    'message_no' => $messageCollection['messageNo'],
                                                    'in_reply_to_id' => $messageCollection['InReplyToId'],
                                                    'reply_to_id' => $messageCollection['replyToId'],
                                                    'subject' => $subject1,
                                                    'from' => $messageCollection['From'],
                                                    'to' => $toMessages,
                                                    'cc' => $ccMessages,
                                                    'bcc' => $bccMessages,
                                                    'date' => $messageCollection['Date'],
                                                    'body' => $messageCollection['Body'],
                                                ]
                                            );

                                            if ($threadImapModel->wasRecentlyCreated) {
                                                $cntThread++;
                                                array_push(
                                                    $insertedThreadObjCollections,
                                                    $this->insertedThreadObjCollectionsMethod($threadImapModel, $messageCollection, $toMessages, $ccMessages, $bccMessages)
                                                );
                                            } else {
                                                array_push(
                                                    $insertedThreadObjCollections,
                                                    "No inbound thread found"
                                                );
                                            }
                                        } catch (\Exception $e) {
                                            $errorMessage = $e->getMessage();
                                            $errorCode = $e->getCode();
                                            $errorString = $e->__toString();

                                            array_push(
                                                $insertedThreadObjCollections,
                                                [
                                                    'General message' => 'Inbound thread problem',
                                                    'Message code' => $errorCode,
                                                    'Message info' => $errorMessage,
                                                ]
                                            );
                                        }
                                    } else {
                                        array_push(
                                            $insertedThreadObjCollections,
                                            "No inbound thread found"
                                        );
                                    }
                                } else {
                                    $mydayManagerUserMails = User::where('user_type', 'Property Manager')
                                        ->where('company_id', $messageCollection['CompanyId'])
                                        ->select('email', 'company_id')
                                        ->distinct()
                                        ->get();

                                    $mydayContactUserMails = Contact::where('company_id', $messageCollection['CompanyId'])
                                        ->where(function ($query) {
                                            $query->where('owner', 1)
                                                ->orWhere('tenant', 1)
                                                ->orWhere('supplier', 1)
                                                ->orWhere('seller', 1);
                                        })
                                        ->select('email', 'company_id')
                                        ->distinct()
                                        ->get();

                                    $messageWithMail = MessageWithMail::where(function ($query) use ($mydayManagerUserMails, $mydayContactUserMails, $subject, $messageCollection) {
                                        if (isset($mydayManagerUserMails) && $mydayManagerUserMails->count() > 0) {
                                            foreach ($mydayManagerUserMails as $mydayManagerUserMail) {
                                                $query->orWhere(function ($subQuery) use ($mydayManagerUserMail, $subject, $messageCollection) {
                                                    $subQuery->where('from', $mydayManagerUserMail->email)
                                                        ->where('subject', $subject)
                                                        ->where('to', $messageCollection['From']);
                                                });
                                            }
                                        }

                                        if (isset($mydayContactUserMails) && $mydayContactUserMails->count() > 0) {
                                            foreach ($mydayContactUserMails as $mydayContactUserMail) {
                                                $query->orWhere(function ($subQuery) use ($mydayContactUserMail, $subject, $messageCollection) {
                                                    $subQuery->where('from', $mydayContactUserMail->email)
                                                        ->where('subject', $subject)
                                                        ->where('to', $messageCollection['From']);
                                                });
                                            }
                                        }
                                    })->first();

                                    if ($messageWithMail) {
                                        try {
                                            // // Create or update the record in ThreadImapModel
                                            $threadImapModel = ThreadImapModel::firstOrCreate(
                                                [
                                                    'message_id' => $messageCollection['messageId'],
                                                ],
                                                [
                                                    'imap_model_id' => $messageWithMail->id,  // Set to NULL if no imap_model_id is available
                                                    'message_uid' => $messageCollection['messageUid'],
                                                    'message_no' => $messageCollection['messageNo'],
                                                    'in_reply_to_id' => $messageCollection['InReplyToId'],
                                                    'reply_to_id' => $messageCollection['replyToId'],
                                                    'subject' => $subject1,
                                                    'from' => $messageCollection['From'],
                                                    'to' => $toMessages,
                                                    'cc' => $ccMessages,
                                                    'bcc' => $bccMessages,
                                                    'date' => $messageCollection['Date'],
                                                    'body' => $messageCollection['Body'],
                                                ]
                                            );

                                            // Log::info($threadImapModel);

                                            $messageWithMail->imap_message_id = $messageWithMail->id;
                                            $messageWithMail->save();

                                            if ($threadImapModel->wasRecentlyCreated) {
                                                $cntThread++;
                                                array_push(
                                                    $insertedThreadObjCollections,
                                                    $this->insertedThreadObjCollectionsMethod($threadImapModel, $messageCollection, $toMessages, $ccMessages, $bccMessages)
                                                );
                                            } else {
                                                array_push(
                                                    $insertedThreadObjCollections,
                                                    "No inbound thread found"
                                                );
                                            }
                                        } catch (\Exception $e) {
                                            $errorMessage = $e->getMessage();
                                            $errorCode = $e->getCode();
                                            $errorString = $e->__toString();

                                            array_push(
                                                $insertedThreadObjCollections,
                                                [
                                                    'General message' => 'Inbound thread problem',
                                                    'Message code' => $errorCode,
                                                    'Message info' => $errorMessage,
                                                ]
                                            );
                                        }
                                    } else {
                                        array_push(
                                            $insertedThreadObjCollections,
                                            "No related message found in ImapModel or MessageWithMail"
                                        );
                                    }
                                }
                            }

                            $validEmailDatas["main mail status"] = $cnt . " Effected...";
                            $validEmailDatas["thread mail status"] = $cntThread . " Effected...";
                            $validEmailDatas["main mail objects"] = collect($insertedObjCollections);
                            $validEmailDatas["thread mail objects"] = collect($insertedThreadObjCollections);
                            array_push($validEmails, $validEmailDatas);
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                            $errorCode = $e->getCode();
                            $errorString = $e->__toString();

                            $fetchedIncomingErrorDatas["main mail status"] = $cnt . " Effected...";
                            $fetchedIncomingErrorDatas["thread mail status"] = $cntThread . " Effected...";
                            $fetchedIncomingErrorDatas["message code"] = $errorCode;
                            $fetchedIncomingErrorDatas["message info"] = $errorMessage;
                            $fetchedIncomingErrorDatas["main mail objects"] = collect($insertedObjCollections);
                            $fetchedIncomingErrorDatas["thread mail objects"] = collect($insertedThreadObjCollections);
                            $fetchedIncomingErrorDatas["valid emails"] = $validEmails;
                            $fetchedIncomingErrorDatas["invalid emails"] = $invalidEmails;
                            array_push($fetchedIncomingErrors, $fetchedIncomingErrorDatas);
                        }
                        $client->disconnect();
                    }
                }
            }
            return response()->json([
                'valid emails' => $validEmails,
                'invalid emails' => $invalidEmails,
                'fetched incoming errors' => $fetchedIncomingErrors,
            ]);
        }
    }

    // Messages separating for all, main and thread messages
    protected function getMailMessages($message, $companyId)
    {
        $messageBody = $message->hasHTMLBody() ? $message->getHTMLBody() : ($message->hasTextBody() ? $message->getTextBody() : "No body Message Found");
        $messageBody .= '<br />';
        $attachmentLinks = [];

        if ($message->hasAttachments()) {
            $count = 0;
            $attachments = $message->getAttachments();

            foreach ($attachments as $attachment) {
                try {
                    $filename = $attachment->getName();
                    $content = $attachment->getContent();
                    $folderName = 'emailattachment';
                    $filePath = $folderName . '/' . $message->getUid() . '/' . $filename;

                    Storage::disk('s3')->put($filePath, $content);

                    if (Storage::disk('s3')->exists($filePath)) {
                        $emailattachmentAssetUrl = Storage::disk('s3')->url($filePath);
                        $messageBody .= '&dArr; <a href="' . $emailattachmentAssetUrl . '" target="_blank" download>' . $filename . '</a><br />';
                        $attachmentLinks[] = $emailattachmentAssetUrl;
                    } else {
                        Storage::disk('s3')->deleteDirectory($folderName . '/' . $message->getUid());
                        if ($count == 0) {
                            $messageBody .= 'No attachment parsed';
                        }
                        $attachmentLinks[] = 'No attachment parsed';
                        $count++;
                    }
                } catch (\Exception $e) {
                    Storage::disk('s3')->deleteDirectory($folderName . '/' . $message->getUid());
                    if ($count == 0) {
                        $messageBody .= 'No attachment parsed';
                    }
                    $attachmentLinks[] = 'No attachment parsed';
                    $count++;
                }
            }
        }

        // Wrap the entire $messageBody in a paragraph tag if needed
        $messageBody = '<p>' . $messageBody . '</p>';

        $currIncomingMail = [];
        $currIncomingMail['rawMessage'] = $message;
        $currIncomingMail['messageId'] = $message->message_id;
        $currIncomingMail['messageUid'] = $message->getUid();
        $currIncomingMail['messageNo'] = $message->getMessageNo();
        $currIncomingMail['InReplyToId'] = $message->getInReplyTo();
        $currIncomingMail['replyToId'] = $message->getReplyTo();
        $currIncomingMail['Subject'] = $message->subject;

        // Extracting 'From' address
        if ($message->getFrom() && is_countable($message->getFrom()) && count($message->getFrom()) > 0) {
            $currIncomingMail['From'] = $message->getFrom()[0]->mail;
        } else {
            preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message->from, $matches);
            $currIncomingMail['From'] = !empty($matches) ? $matches[0] : null;
        }

        $currIncomingMail['To'] = $message->to;
        $currIncomingMail['Cc'] = $message->getCc();
        $currIncomingMail['Bcc'] = $message->getBcc();
        $currIncomingMail['Date'] = $message->date;
        $currIncomingMail['Body'] = $messageBody;
        $currIncomingMail['Thread'] = $message->getThread();
        $currIncomingMail['attachmentLinks'] = $attachmentLinks;
        $currIncomingMail['MessageAttributes'] = $message->getAttributes();
        $currIncomingMail['CompanyId'] = $companyId;

        return $currIncomingMail;
    }


    // Insertion into myday system
    protected function insertedObjCollectionsMethod($imapModel, $msg, $to, $cc, $bcc)
    {
        $mydayManagerUserMails = User::where('user_type', 'Property Manager')
            ->where('company_id', $msg['CompanyId'])
            ->select('email', 'company_id')
            ->distinct()
            ->get();

        $mydayContactUserMails = Contact::where('company_id', $msg['CompanyId'])
            ->where(function ($query) {
                $query->where('owner', 1)
                    ->orWhere('tenant', 1)
                    ->orWhere('supplier', 1)
                    ->orWhere('seller', 1);
            })
            ->select('email', 'company_id')
            ->distinct()
            ->get();

        $obj = [];
        if (isset($mydayManagerUserMails) && $mydayManagerUserMails->count() > 0) {
            foreach ($mydayManagerUserMails as $mydayManagerUserMail) {
                $myDayMailObj = new MessageWithMail;
                $myDayMailObj->to = $mydayManagerUserMail->email;
                $myDayMailObj->from = $msg['From'];
                $myDayMailObj->subject = $msg['Subject'];
                $myDayMailObj->body = $msg['Body'];
                $myDayMailObj->status = 'sent';
                $myDayMailObj->type = 'email';
                $myDayMailObj->watch = 1;
                $myDayMailObj->company_id = $mydayManagerUserMail->company_id;
                $myDayMailObj->cc = $cc;
                $myDayMailObj->bcc = $bcc;
                $myDayMailObj->reply_to = $mydayManagerUserMail->email;
                $myDayMailObj->imap_message_id = $imapModel->id;
                $myDayMailObj->save();
            }

            $obj1 = [
                'status' => 'Inbound mail inserted for property manager to myday',
                'rawMessage' => $msg['rawMessage'],
                'imapId' => $imapModel->id,
                'message_id' => $msg['messageId'],
                'message_uid' => $msg['messageUid'],
                'message_no' => $msg['messageNo'],
                'subject' => $msg['Subject'],
                'from' => $msg['From'],
                'in_reply_to_id' => $msg['InReplyToId'],
                'reply_to_id' => $msg['replyToId'],
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
                'date' => $msg['Date'],
                'body' => $msg['Body'],
                'attachmentLinks' => $msg['attachmentLinks'],
                'companyId' => $msg['CompanyId'],
                'MessageAttributes' => $msg['MessageAttributes'],
            ];
            array_push($obj, $obj1);
        } else {
            $obj1 = [
                'message' => 'No inbound mail inserted for property manager to myday',
            ];
            array_push($obj, $obj1);
        }

        if (isset($mydayContactUserMails) && $mydayContactUserMails->count() > 0) {
            foreach ($mydayContactUserMails as $mydayContactUserMail) {
                $myDayMailObj = new MessageWithMail;
                $myDayMailObj->to = $mydayContactUserMail->email;
                $myDayMailObj->from = $msg['From'];
                $myDayMailObj->subject = $msg['Subject'];
                $myDayMailObj->body = $msg['Body'];
                $myDayMailObj->status = 'sent';
                $myDayMailObj->type = 'email';
                $myDayMailObj->watch = 1;
                $myDayMailObj->company_id = $mydayContactUserMail->company_id;
                $myDayMailObj->cc = $cc;
                $myDayMailObj->bcc = $bcc;
                $myDayMailObj->reply_to = $mydayContactUserMail->email;
                $myDayMailObj->imap_message_id = $imapModel->id;
                $myDayMailObj->save();
            }

            $obj2 = [
                'status' => 'Inbound mail inserted for owner, tenant, supplier, seller to myday',
                'rawMessage' => $msg['rawMessage'],
                'imapId' => $imapModel->id,
                'message_id' => $msg['messageId'],
                'message_uid' => $msg['messageUid'],
                'message_no' => $msg['messageNo'],
                'subject' => $msg['Subject'],
                'from' => $msg['From'],
                'in_reply_to_id' => $msg['InReplyToId'],
                'reply_to_id' => $msg['replyToId'],
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
                'date' => $msg['Date'],
                'body' => $msg['Body'],
                'attachmentLinks' => $msg['attachmentLinks'],
                'companyId' => $msg['CompanyId'],
                'MessageAttributes' => $msg['MessageAttributes'],
            ];
            array_push($obj, $obj2);
        } else {
            $obj2 = [
                'message' => 'No inbound mail inserted for owner, tenant, supplier, seller to myday',
            ];
            array_push($obj, $obj2);
        }
        return $obj;
    }

    // Insertion thread into myday system
    protected function insertedThreadObjCollectionsMethod($threadImapModel, $msg, $to, $cc, $bcc)
    {
        $imapModelId = ThreadImapModel::find($threadImapModel->id)->imap_model_id;
        $messageWithMailObjs = MessageWithMail::where('imap_message_id', $imapModelId)
            ->where('company_id', $msg['CompanyId'])
            ->get();

        if (isset($messageWithMailObjs) && $messageWithMailObjs->count() > 0) {
            foreach ($messageWithMailObjs as $messageWithMailObj) {
                $myDayReplyMailObj = new MessageWithMailReply;
                $myDayReplyMailObj->master_mail_id = $messageWithMailObj->id;
                $myDayReplyMailObj->to = $messageWithMailObj->to;
                $myDayReplyMailObj->from = $msg['From'];
                $myDayReplyMailObj->subject = $msg['Subject'];
                $myDayReplyMailObj->body = $msg['Body'];
                $myDayReplyMailObj->status = $messageWithMailObj->status;
                $myDayReplyMailObj->company_id = $messageWithMailObj->company_id;
                $myDayReplyMailObj->save();

                MessageWithMail::where('id', $messageWithMailObj->id)->update([
                    "reply_to" => $messageWithMailObj->to,
                    "reply_from"       => $messageWithMailObj->from,
                    "reply_type" => 1,
                    "watch" => 1,
                ]);
            }

            $obj = [
                'status' => 'Inbound thread inserted',
                'rawMessage' => $msg['rawMessage'],
                'imapId' => $threadImapModel->id,
                'message_id' => $msg['messageId'],
                'message_uid' => $msg['messageUid'],
                'message_no' => $msg['messageNo'],
                'subject' => $msg['Subject'],
                'from' => $msg['From'],
                'in_reply_to_id' => $msg['InReplyToId'],
                'reply_to_id' => $msg['replyToId'],
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
                'date' => $msg['Date'],
                'body' => $msg['Body'],
                'attachmentLinks' => $msg['attachmentLinks'],
                'companyId' => $msg['CompanyId'],
                'MessageAttributes' => $msg['MessageAttributes'],
            ];
            return $obj;
        } else {
            $obj = [
                'message' => 'No inbound thread inserted to myday',
            ];
            return $obj;
        }
    }

    protected function isBase64($string)
    {
        return base64_encode(base64_decode($string, true)) === $string;
    }
}
