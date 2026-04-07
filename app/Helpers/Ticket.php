<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Cfg;
use App\Helpers\LogActivity;
use App\Helpers\Customfield;
use App\Models\Ticket as TicketModel;
use App\Models\Ticketdepartment;
use App\Models\Ticketnote;
use App\Models\Ticketfeedback;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Ticketreply;
use App\Models\Tickettag;
use App\Models\Ticketlog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Application;
use Illuminate\Support\Facades\Lang;

class Ticket
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public static function GetDepartmentName($deptId)
    {
        static $departmentNames = null;
        if (is_null($departmentNames)) {
            $departmentNames = Ticketdepartment::all()->pluck("name", "id")->toArray();
        }
        return $departmentNames[$deptId] ?? "";
    }

    public static function openNewTicket($userid, $contactid, $deptid, $tickettitle, $message, $urgency, $attachmentsString = "", array $from = [], $relatedservice = "", $ccemail = "", $noemail = "", $admin = "", $markdown = false)
    {
        global $CONFIG;
        $data = Ticketdepartment::where("id", $deptid)->first();
        if (!$data) {
            exit("Department Not Found. Exiting.");
        }
        $deptid = $data->id;
        $noautoresponder = $data->noautoresponder;

        $ccemail = trim($ccemail);
        $tickettitle = self::processUtf8Mb4($tickettitle);
        $message = self::processUtf8Mb4($message);

        if ($userid) {
            $data = $contactid > 0 ? Contact::where("id", $contactid)->where("userid", $userid)->first() : Client::where("id", $userid)->first();
            if ($admin) {
                $message = str_replace(["[NAME]", "[FIRSTNAME]", "[EMAIL]"], [$data->firstname . " " . $data->lastname, $data->firstname, $data->email], $message);
            }
            $clientname = $data->firstname . " " . $data->lastname;
        } else {
            if ($admin) {
                $message = str_replace(["[NAME]", "[FIRSTNAME]", "[EMAIL]"], [$from["name"] ?? "", current(explode(" ", $from["name"] ?? "")), $from["email"] ?? ""], $message);
            }
            $clientname = $from["name"];
        }

        $ccemail = implode(",", array_filter(array_unique(explode(",", $ccemail)), function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        $c = Str::random(8);
        $tid = self::genTicketMask();
        $urgency = in_array($urgency, ["High", "Medium", "Low"]) ? $urgency : "Medium";
        $editor = $markdown ? "markdown" : "plain";

        $ticketData = [
            "tid" => $tid,
            "userid" => $userid,
            "contactid" => $contactid,
            "did" => $deptid,
            "date" => now(),
            "title" => $tickettitle,
            "message" => $message,
            "urgency" => $urgency,
            "status" => "Open",
            "attachment" => $attachmentsString,
            "lastreply" => now(),
            "name" => $from["name"] ?? "",
            "email" => $from["email"] ?? "",
            "c" => $c,
            "clientunread" => "1",
            "adminunread" => "",
            "service" => $relatedservice,
            "cc" => $ccemail,
            "editor" => $editor
        ];

        if ($admin) {
            $ticketData["admin"] = \App\Helpers\AdminFunctions::getAdminName();
        }

        $id = DB::table("tbltickets")->insertGetId($ticketData);
        $tid = self::genTicketMask($id);
        TicketModel::where("id", $id)->update(["tid" => $tid]);

        if (!$noemail) {
            $messageType = $admin ? "Support Ticket Opened by Admin" : "Support Ticket Opened";
            if (!$admin && !$noautoresponder) {
                \App\Helpers\Functions::sendMessage($messageType, $id);
            }
        }

        $deptname = self::GetDepartmentName($deptid);
        if (!$noemail) {
            $changes = [
                "Opened" => ["new" => $message],
                "Who" => $admin ? $ticketData["admin"] : $clientname
            ];
            if ($attachmentsString) {
                $changes["Attachments"] = self::ticketgenerateattachmentslistfromstring($attachmentsString);
            }
            \App\Helpers\Tickets::notifyTicketChanges($id, $changes, self::getDepartmentNotificationIds($deptid));
        }

        self::addticketlog($id, "New Support Ticket Opened");
        $hookType = $admin ? "TicketOpenAdmin" : "TicketOpen";
        \App\Helpers\Hooks::run_hook($hookType, [
            "ticketid" => $id,
            "ticketmask" => $tid,
            "userid" => $userid,
            "deptid" => $deptid,
            "deptname" => $deptname,
            "subject" => $tickettitle,
            "message" => $message,
            "priority" => $urgency
        ]);

        return ["ID" => $id, "TID" => $tid, "C" => $c, "Subject" => $tickettitle];
    }

    public static function AddReply($ticketid, $userid, $contactid, $message, $admin, $attachmentsString = "", $from = "", $status = "", $noemail = "", $api = false, $markdown = false, $changes = [])
    {
        global $CONFIG;
        if (!is_array($from)) {
            $from = ["name" => "", "email" => ""];
        }
        $adminname = "";
        $message = self::processUtf8Mb4($message);

        if ($admin) {
            $data = TicketModel::where("id", $ticketid)->first();
            if ($data->userid > 0) {
                $data = $data->contactid > 0 ? Contact::where("id", $data->contactid)->where("userid", $data->userid)->first() : Client::where("id", $data->userid)->first();
                $message = str_replace(["[NAME]", "[FIRSTNAME]", "[EMAIL]"], [$data->firstname . " " . $data->lastname, $data->firstname, $data->email], $message);
            } else {
                $message = str_replace(["[NAME]", "[FIRSTNAME]", "[EMAIL]"], [$data->name, current(explode(" ", $data->name)), $data->email], $message);
            }
            $adminname = $api ? $admin : \App\Helpers\AdminFunctions::getAdminName((int)$admin);
        }

        $editor = $markdown ? "markdown" : "plain";
        $ticketreplyid = Ticketreply::create([
            "tid" => $ticketid,
            "userid" => $userid,
            "contactid" => $contactid ?? 0,
            "name" => $from["name"] ?? "",
            "email" => $from["email"] ?? "",
            "date" => now(),
            "message" => $message,
            "admin" => $adminname,
            "attachment" => $attachmentsString,
            "editor" => $editor
        ])->id;

        $data = TicketModel::find($ticketid, ["tid", "did", "title", "urgency", "flag", "status"]);
        $tid = $data->tid;
        $deptid = $data->did;
        $tickettitle = $data->title;
        $urgency = $data->urgency;
        $flagadmin = $data->flag;
        $oldStatus = $data->status;

        $clientname = $userid || $contactid ? ($contactid ? Contact::find($contactid)->fullName : Client::find($userid)->fullName) : $from["name"];
        $deptname = self::GetDepartmentName($deptid);

        if ($admin) {
            $status = $status ?: "Answered";
            $updateqry = ["status" => $status, "clientunread" => "1", "lastreply" => now()];
            if (isset($CONFIG["TicketLastReplyUpdateClientOnly"]) && $CONFIG["TicketLastReplyUpdateClientOnly"]) {
                unset($updateqry["lastreply"]);
            }
            TicketModel::where("id", $ticketid)->update($updateqry);
            self::addticketlog($ticketid, "New Ticket Response");
            if (!$noemail) {
                \App\Helpers\Functions::sendMessage("Support Ticket Reply", $ticketid, $ticketreplyid);
            }
            \App\Helpers\Hooks::run_hook("TicketAdminReply", [
                "ticketid" => $ticketid,
                "replyid" => $ticketreplyid,
                "deptid" => $deptid,
                "deptname" => $deptname,
                "subject" => $tickettitle,
                "message" => $message,
                "priority" => $urgency,
                "admin" => $adminname,
                "status" => $status
            ]);
        } else {
            $status = "Customer-Reply";
            $updateqry = ["status" => "Customer-Reply", "clientunread" => "1", "adminunread" => "", "lastreply" => now()];
            $UpdateLastReplyTimestamp = Cfg::get("UpdateLastReplyTimestamp");
            if ($UpdateLastReplyTimestamp == "statusonly" && ($oldStatus == $status || $oldStatus == "Open" && $status == "Customer-Reply")) {
                unset($updateqry["lastreply"]);
            }
            TicketModel::where("id", $ticketid)->update($updateqry);
            self::addticketlog($ticketid, "New Ticket Response made by User");
            \App\Helpers\Hooks::run_hook("TicketUserReply", [
                "ticketid" => $ticketid,
                "replyid" => $ticketreplyid,
                "userid" => $userid,
                "deptid" => $deptid,
                "deptname" => $deptname,
                "subject" => $tickettitle,
                "message" => $message,
                "priority" => $urgency,
                "status" => $status
            ]);
        }

        if ($oldStatus != $status) {
            $changes["Status"] = ["old" => $oldStatus, "new" => $status];
        }
        $changes["Reply"] = ["new" => $message];
        if ($attachmentsString) {
            $changes["Attachments"] = self::ticketgenerateattachmentslistfromstring($attachmentsString);
        }

        $recipients = [];
        if (!$admin) {
            $changes["Who"] = $clientname;
            $recipients = $flagadmin ? [$flagadmin] : (!$noemail ? self::getDepartmentNotificationIds($deptid) : []);
        } else {
            $changes["Who"] = $adminname;
        }
        \App\Helpers\Tickets::notifyTicketChanges($ticketid, $changes, $recipients);
    }

    public static function processUtf8Mb4($message)
    {
        $cutUtf8Mb4 = Cfg::get("CutUtf8Mb4");
        if (!$cutUtf8Mb4) {
            return $message;
        }
        $emojis = [
            "/[\\x{1F600}\\x{1F601}]/u" => ":)",
            "/[\\x{1F603}-\\x{1F606}]/u" => ":D",
            "/[\\x{1F609}\\x{1F60A}]/u" => ";)",
            "/\\x{1F610}/u" => ":|",
            "/[\\x{1F612}\\x{1F61E}\\x{1F61F}]/u" => ":(",
            "/\\x{1F61B}/u" => ":P",
            "/\\x{1F622}/u" => ":'("
        ];
        $cleanText = preg_replace(array_keys($emojis), array_values($emojis), $message);
        $removePatterns = [
            "/[\\x{1F600}-\\x{1F64F}]/u",
            "/[\\x{1F300}-\\x{1F5FF}]/u",
            "/[\\x{1F680}-\\x{1F6FF}]/u",
            "/[\\x{2600}-\\x{26FF}]/u",
            "/[\\x{2700}-\\x{27BF}]/u"
        ];
        return preg_replace($removePatterns, "", $cleanText);
    }

    public static function AddNote($tid, $message, $markdown = false, $attachments = "")
    {
        $auth = Auth::guard('admin')->user();
        $adminid = $auth ? $auth->id : 0;

        Ticketnote::create([
            "ticketid" => $tid,
            "date" => now(),
            "admin" => $auth ? $auth->firstname : "system",
            "message" => $message,
            "attachments" => $attachments,
            "editor" => $markdown ? "markdown" : "plain"
        ]);

        self::addTicketLog($tid, "Ticket Note Added");
        \App\Helpers\Hooks::run_hook("TicketAddNote", [
            "ticketid" => $tid,
            "message" => $message,
            "adminid" => $adminid,
            "attachments" => $attachments
        ]);
    }

    public static function GenTicketMask($id = "")
    {
        $lowercase = "abcdefghijklmnopqrstuvwxyz";
        $uppercase = "ABCDEFGHIJKLMNOPQRSTUVYWXYZ";
        $ticketmaskstr = "";
        $ticketmask = trim(Cfg::get("TicketMask")) ?: "%n%n%n%n%n%n";

        for ($i = 0, $masklen = strlen($ticketmask); $i < $masklen; $i++) {
            $maskval = $ticketmask[$i];
            if ($maskval == "%") {
                $i++;
                $maskval .= $ticketmask[$i];
                switch ($maskval) {
                    case "%A":
                        $ticketmaskstr .= $uppercase[rand(0, 25)];
                        break;
                    case "%a":
                        $ticketmaskstr .= $lowercase[rand(0, 25)];
                        break;
                    case "%n":
                        $ticketmaskstr .= strlen($ticketmaskstr) ? rand(0, 9) : rand(1, 9);
                        break;
                    case "%y":
                        $ticketmaskstr .= date("Y");
                        break;
                    case "%m":
                        $ticketmaskstr .= date("m");
                        break;
                    case "%d":
                        $ticketmaskstr .= date("d");
                        break;
                    case "%i":
                        $ticketmaskstr .= $id;
                        break;
                }
            } else {
                $ticketmaskstr .= $maskval;
            }
        }

        if (TicketModel::where('tid', $ticketmaskstr)->exists()) {
            return self::GenTicketMask($id);
        }
        return $ticketmaskstr;
    }

    public static function DeleteTicket($ticketid, $replyid = 0)
    {
        $auth = Auth::guard('admin')->user();
        $ticketid = (int)$ticketid;
        $replyid = (int)$replyid;
        $attachments = [];

        $ticketreplies = Ticketreply::select('attachment')->where($replyid > 0 ? 'id' : 'tid', $replyid > 0 ? $replyid : $ticketid)->get();
        foreach ($ticketreplies as $ticketreply) {
            $attachments[] = $ticketreply->attachment;
        }

        if (!$replyid) {
            $data = TicketModel::select('did', 'attachment')->where('id', $ticketid)->first();
            $deptid = $data->did;
            $attachments[] = $data->attachment;
        }

        foreach ($attachments as $attachment) {
            if ($attachment) {
                foreach (explode("|", $attachment) as $filename) {
                    Storage::disk('attachments')->delete($filename);
                }
            }
        }

        if (!$replyid) {
            $customfields = Customfield::getCustomFields("support", $deptid, $ticketid, true);
            foreach ($customfields as $field) {
                \App\Models\Customfieldsvalue::where('fieldid', $field["id"])->where('relid', $ticketid)->delete();
            }

            Tickettag::where('ticketid', $ticketid)->delete();
            Ticketnote::where('ticketid', $ticketid)->delete();
            Ticketlog::where('tid', $ticketid)->delete();
            Ticketreply::where('tid', $ticketid)->delete();
            TicketModel::where('id', $ticketid)->delete();
            LogActivity::Save("Deleted Ticket - Ticket ID: " . $ticketid);
            $adminid = $auth ? $auth->id : 0;
            \App\Helpers\Hooks::run_hook("TicketDelete", ["ticketId" => $ticketid, "adminId" => $adminid]);
        } else {
            Ticketreply::where('id', $replyid)->delete();
            self::addticketlog($ticketid, "Deleted Ticket Reply (ID: " . $replyid . ")");
            LogActivity::Save("Deleted Ticket Reply - ID: " . $replyid);
            $adminid = $auth ? $auth->id : 0;
            \App\Helpers\Hooks::run_hook("TicketDeleteReply", ["ticketId" => $ticketid, "replyId" => $replyid, "adminId" => $adminid]);
        }
    }

    public static function CloseTicket($id)
    {
        $ticket = DB::table("tbltickets")->find($id);
        if (is_null($ticket) || $ticket->status == "Closed") {
            return false;
        }

        $changes = [];
        if (defined("CLIENTAREA") || Application::isClientAreaRequest()) {
            self::addticketlog($id, "Closed by Client");
            $changes["Who"] = session("cid") ? Contact::find(session("cid"))->fullName : Client::find(Auth::user()->id)->fullName;
        } else {
            if (defined("ADMINAREA") || defined("APICALL") || Application::isAdminAreaRequest() || Application::isApiRequest()) {
                self::addticketlog($id, "Status changed to Closed");
                $changes["Who"] = \App\Helpers\AdminFunctions::getAdminName(Auth::guard('admin')->user() ? Auth::guard('admin')->user()->id : 0);
            } else {
                self::addticketlog($id, "Ticket Auto Closed For Inactivity");
                $changes["Who"] = "System";
            }
        }

        $changes["Status"] = ["old" => $ticket->status, "new" => "Closed"];
        TicketModel::where('id', $ticket->id)->update(["status" => "Closed"]);

        $skipFeedbackRequest = false;
        $skipNotification = false;
        $responses = \App\Helpers\Hooks::run_hook("TicketClose", ["ticketid" => $id]);
        foreach ($responses as $response) {
            if (!empty($response["skipFeedbackRequest"])) {
                $skipFeedbackRequest = true;
            }
            if (!empty($response["skipNotification"])) {
                $skipNotification = true;
            }
        }

        if (!$skipFeedbackRequest) {
            $department = DB::table("tblticketdepartments")->find($ticket->did);
            if ($department->feedback_request) {
                $feedbackcheck = Ticketfeedback::where("ticketid", $id)->exists();
                if (!$feedbackcheck) {
                    \App\Helpers\Functions::sendMessage("Support Ticket Feedback Request", $id);
                }
            }
        }

        if (!$skipNotification) {
            \App\Helpers\Tickets::notifyTicketChanges($id, $changes);
        }

        return true;
    }

    public static function getStatusColour($tstatus, $htmlOutput = true)
    {
        global $_LANG;
        static $ticketcolors = [];

        if (!array_key_exists($tstatus, $ticketcolors)) {
            $ticketcolors[$tstatus] = $color = \App\Models\Ticketstatus::select("color")->where("title", $tstatus)->value("color");
        } else {
            $color = $ticketcolors[$tstatus];
        }

        if ($htmlOutput) {
            $langstatus = preg_replace("/[^a-z]/i", "", strtolower($tstatus));
            $tstatus = $_LANG["supportticketsstatus" . $langstatus] ?? __("client.supportticketsstatus{$langstatus}");
            $statuslabel = $color ? "<span style=\"color:{$color}\">{$tstatus}</span>" : $tstatus;
            return $statuslabel;
        }

        return $color;
    }

    public static function addTicketLog($tid, $action)
    {
        $auth = Auth::guard('admin')->user();
        if ($auth) {
            $action .= " (by " . $auth->name . ")";
        }
        \App\Models\Ticketlog::insert(["date" => \Carbon\Carbon::now(), "tid" => $tid, "action" => $action]);
    }

    public static function ticketGenerateAttachmentsListFromString($attachmentsString)
    {
        $attachmentsOutput = "";
        $attachmentsString = trim($attachmentsString);
        if ($attachmentsString) {
            $attachmentsOutput .= "<br /><br /><strong>Attachments</strong><br />";
            $attachments = explode("|", $attachmentsString);
            foreach ($attachments as $i => $attachment) {
                $attachmentsOutput .= ($i + 1) . ". " . substr($attachment, 7) . "<br />";
            }
        }
        return $attachmentsOutput;
    }

    public static function getDepartmentNotificationIds($departmentId)
    {
        $admins = \App\User\Admin::join("tbladminroles", "tbladmins.roleid", "=", "tbladminroles.id")
            ->where("tbladmins.disabled", "=", "0")
            ->where("tbladminroles.supportemails", "=", "1")
            ->where("tbladmins.ticketnotifications", "!=", "")
            ->get(["tbladmins.id", "tbladmins.supportdepts", "tbladmins.ticketnotifications"]);

        $notificationAdmins = [];
        foreach ($admins as $admin) {
            if (in_array($departmentId, $admin->supportDepartmentIds)) {
                $notificationAdmins[] = $admin->id;
            }
        }
        return $notificationAdmins;
    }

    public static function notifyTicketChanges($ticketId, array $changes, array $recipients = [], array $removeRecipients = [])
    {
        if ($ticketId) {
            $ticket = \App\Models\Ticket::with('client')->find($ticketId);
            $mergeFields = [
                "ticket_id" => $ticketId,
                "ticket_tid" => $ticket->tid,
                "client_name" => $ticket->client->firstname . ' ' . $ticket->client->lastname,
                "client_id" => $ticket->userid,
                "ticket_department" => \App\Models\Ticketdepartment::find($ticket->did)->name,
                "ticket_subject" => $ticket->title,
                "ticket_priority" => $ticket->urgency,
                "changer" => $changes["Who"] ?? '',
                "changes" => $changes
            ];

            if (!empty($changes["Reply"])) {
                $markup = new \App\Helpers\ViewMarkup();
                $markupFormat = $markup->determineMarkupEditor("ticket_reply", $ticket->editor);
                $mergeFields["newReply"] = $markup->transform($changes["Reply"]["new"], $markupFormat);
                unset($changes["Reply"]);
            }

            if (!empty($changes["Note"])) {
                $markup = $markup ?? new \App\Helpers\ViewMarkup();
                $markupFormat = $markup->determineMarkupEditor("ticket_note", $changes["note"]["editor"]);
                $mergeFields["newNote"] = $markup->transform($changes["Note"]["new"], $markupFormat);
                unset($changes["Note"]);
            }

            if (!empty($changes["Opened"]) && !isset($markup)) {
                $markup = new \App\Helpers\ViewMarkup();
                $markupFormat = $markup->determineMarkupEditor("ticket_note", $ticket->getData("editor"));
                $mergeFields["newTicket"] = $markup->transform($changes["Opened"]["new"], $markupFormat);
            }

            if (!empty($changes["Attachments"])) {
                $mergeFields["newAttachments"] = $changes["Attachments"];
                unset($changes["Attachments"]);
            }

            $includeFlagged = true;
            if (!empty($changes["Assigned To"])) {
                if ($changes["Assigned To"]["newId"] == Auth::guard('admin')->user()->id) {
                    $includeFlagged = false;
                }
                if ($changes["Assigned To"]["oldId"] && $changes["Assigned To"]["oldId"] != Auth::guard('admin')->user()->id) {
                    $recipients = array_merge($recipients, [$changes["Assigned To"]["oldId"]]);
                }
            }

            if (!empty($changes["Department"])) {
                $recipients = array_merge($recipients, self::getDepartmentNotificationIds($changes["Department"]["newId"]));
            }

            $recipients = array_unique(array_merge(
                $ticket->flag && $includeFlagged ? [$ticket->flag] : [],
                $recipients,
                \App\Models\TicketWatcher::where('ticket_id', $ticket->ticketid)->pluck("admin_id")->all()
            ));

            if ($removeRecipients) {
                $recipients = array_filter($recipients, function ($value) use ($removeRecipients) {
                    return !in_array($value, $removeRecipients);
                });
            }

            $recipients = array_flip($recipients);
            if (isset($recipients[(int) Auth::guard('admin')->user()->id])) {
                unset($recipients[(int) Auth::guard('admin')->user()->id]);
            }
            $recipients = array_flip($recipients);

            if (count($recipients) > 0) {
                return \App\Helpers\Functions::sendAdminMessage("Support Ticket Change Notification", $mergeFields, "ticket_changes", $ticket->tid, $recipients);
            }
        }
        return false;
    }

    public static function getTimeBetweenDates($lastreply, $from = "now")
    {
        $datetime = strtotime($from);
        $date2 = strtotime($lastreply);
        $holdtotsec = $datetime - $date2;
        $holdtotmin = $holdtotsec / 60;
        $holdtothr = $holdtotsec / 3600;
        $holdtotday = intval($holdtotsec / 86400);
        $holdhr = intval($holdtothr - $holdtotday * 24);
        $holdmr = intval($holdtotmin - ($holdhr * 60 + $holdtotday * 1440));
        $holdsr = intval($holdtotsec - ($holdhr * 3600 + $holdmr * 60 + 86400 * $holdtotday));
        return ["days" => $holdtotday, "hours" => $holdhr, "minutes" => $holdmr, "seconds" => $holdsr];
    }

    public static function getShortLastReplyTime($lastreply)
    {
        $timeparts = self::getTimeBetweenDates($lastreply);
        $str = "";
        if ($timeparts["days"] > 0) {
            $str .= $timeparts["days"] . "d ";
        }
        $str .= $timeparts["hours"] . "h ";
        $str .= $timeparts["minutes"] . "m";
        return $str;
    }

    public static function getLastReplyTime($lastreply)
    {
        $timeparts = self::getTimeBetweenDates($lastreply);
        $str = "";
        if ($timeparts["days"] > 0) {
            $str .= $timeparts["days"] . " Days ";
        }
        $str .= $timeparts["hours"] . " Hours ";
        $str .= $timeparts["minutes"] . " Minutes ";
        $str .= $timeparts["seconds"] . " Seconds Ago";
        return $str;
    }

    public static function getTicketDuration($start, $end)
    {
        $timeparts = self::getTimeBetweenDates($start, $end);
        $str = "";
        if ($timeparts["days"] > 0) {
            $str .= $timeparts["days"] . " " . Lang::get("client.days") . " ";
        }
        if ($timeparts["hours"] > 0) {
            $str .= $timeparts["hours"] . " " . Lang::get("client.hours") . " ";
        }
        if ($timeparts["minutes"] > 0) {
            $str .= $timeparts["minutes"] . " " . Lang::get("client.minutes") . " ";
        }
        $str .= $timeparts["seconds"] . " " . Lang::get("client.seconds") . " ";
        return $str;
    }

    public static function checkTicketAttachmentExtension($file_name)
    {
        return \App\Helpers\FileUpload::isExtensionAllowed($file_name);
    }

    // public static function uploadTicketAttachments($isAdmin = false)
    // {
    //     $attachments = Request::file('attachments');
    //     $attachmentString = [];
        
    //     if (Request::hasFile('attachments')) {
    //         $directory = 'Files/';
            
    //         foreach ($attachments as $attachment) {
    //             $uuid = (string) Str::uuid();
    //             $filename = $uuid . "." . $attachment->getClientOriginalExtension();
    //             $content = file_get_contents($attachment);

    //             // Ensure the directory exists
    //             if (!file_exists($directory)) {
    //                 mkdir($directory, 0755, true);
    //             }

    //             // Save the file
    //             file_put_contents($directory . $filename, $content);

    //             // Store the full path in the attachmentString
    //             $attachmentString[] = 'Files/' . $filename;
    //         }
    //     }
    //     $attachmentString = implode('|', $attachmentString);
    //     return $attachmentString;
    //     // $attachmentString = [];
    //     // if (Request::hasFile('attachments')) {
    //     //     foreach ($attachments as $attachment) {
    //     //         $fileNameToSave = Str::random(6) . "_" . $attachment->getClientOriginalName();
    //     //         $filepath = "{$fileNameToSave}";

    //     //         if ($isAdmin || \App\Helpers\FileUpload::isExtensionAllowed($attachment->getClientOriginalName())) {
    //     //             Storage::disk('attachments')->put($filepath, file_get_contents($attachment), 'public');
    //     //             $attachmentString[] = $fileNameToSave;
    //     //         }
    //     //     }
    //     // }
    //     // return implode('|', $attachmentString);
    // }

    public static function uploadTicketAttachments($isAdmin = false)
    {
        $attachments = Request::file('attachments');
        $attachmentString = [];

        if (Request::hasFile('attachments')) {
            $directory = 'Files/';

            foreach ($attachments as $attachment) {
                // Get original filename
                $originalName = $attachment->getClientOriginalName();

                // Ensure the directory exists
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save the file with original name
                $attachment->move($directory, $originalName);

                // Store the full path in the attachmentString
                $attachmentString[] = 'Files/' . $originalName;
            }
        }
        $attachmentString = implode('|', $attachmentString);
        return $attachmentString;
    }

    public static function ClientRead($tid)
    {
        \App\Models\Ticket::where("id", $tid)->update(["clientunread" => ""]);
    }

    public static function getKBAutoSuggestions($text)
    {
        $kbarticles = [];
        $hookret = \App\Helpers\Hooks::run_hook("SubmitTicketAnswerSuggestions", ["text" => $text]);
        if (count($hookret)) {
            foreach ($hookret as $hookdat) {
                foreach ($hookdat as $arrdata) {
                    $kbarticles[] = $arrdata;
                }
            }
        } else {
            $ignorewords = ["able", "about", "above", "according", "accordingly", "across", "actually", "after", "afterwards", "again", "against", "ain't", "allow", "allows", "almost", "alone", "along", "already", "also", "although", "always", "among", "amongst", "another", "anybody", "anyhow", "anyone", "anything", "anyway", "anyways", "anywhere", "apart", "appear", "appreciate", "appropriate", "aren't", "around", "aside", "asking", "associated", "available", "away", "awfully", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "believe", "below", "beside", "besides", "best", "better", "between", "beyond", "both", "brief", "c'mon", "came", "can't", "cannot", "cant", "cause", "causes", "certain", "certainly", "changes", "clearly", "come", "comes", "concerning", "consequently", "consider", "considering", "contain", "containing", "contains", "corresponding", "could", "couldn't", "course", "currently", "definitely", "described", "despite", "didn't", "different", "does", "doesn't", "doing", "don't", "done", "down", "downwards", "during", "each", "eight", "either", "else", "elsewhere", "enough", "entirely", "especially", "even", "ever", "every", "everybody", "everyone", "everything", "everywhere", "exactly", "example", "except", "fifth", "first", "five", "followed", "following", "follows", "former", "formerly", "forth", "four", "from", "further", "furthermore", "gets", "getting", "given", "gives", "goes", "going", "gone", "gotten", "greetings", "hadn't", "happens", "hardly", "hasn't", "have", "haven't", "having", "he's", "hello", "help", "hence", "here", "here's", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "himself", "hither", "hopefully", "howbeit", "however", "i'll", "i've", "ignored", "immediate", "inasmuch", "indeed", "indicate", "indicated", "indicates", "inner", "insofar", "instead", "into", "inward", "isn't", "it'd", "it'll", "it's", "itself", "just", "keep", "keeps", "kept", "know", "known", "knows", "last", "lately", "later", "latter", "latterly", "least", "less", "lest", "let's", "like", "liked", "likely", "little", "look", "looking", "looks", "mainly", "many", "maybe", "mean", "meanwhile", "merely", "might", "more", "moreover", "most", "mostly", "much", "must", "myself", "name", "namely", "near", "nearly", "necessary", "need", "needs", "neither", "never", "nevertheless", "next", "nine", "nobody", "none", "noone", "normally", "nothing", "novel", "nowhere", "obviously", "often", "okay", "once", "ones", "only", "onto", "other", "others", "otherwise", "ought", "ours", "ourselves", "outside", "over", "overall", "particular", "particularly", "perhaps", "placed", "please", "plus", "possible", "presumably", "probably", "provides", "quite", "rather", "really", "reasonably", "regarding", "regardless", "regards", "relatively", "respectively", "right", "said", "same", "saying", "says", "second", "secondly", "seeing", "seem", "seemed", "seeming", "seems", "seen", "self", "selves", "sensible", "sent", "serious", "seriously", "seven", "several", "shall", "should", "shouldn't", "since", "some", "somebody", "somehow", "someone", "something", "sometime", "sometimes", "somewhat", "somewhere", "soon", "sorry", "specified", "specify", "specifying", "still", "such", "sure", "take", "taken", "tell", "tends", "than", "thank", "thanks", "thanx", "that", "that's", "thats", "their", "theirs", "them", "themselves", "then", "thence", "there", "there's", "thereafter", "thereby", "therefore", "therein", "theres", "thereupon", "these", "they", "they'd", "they'll", "they're", "they've", "think", "third", "this", "thorough", "thoroughly", "those", "though", "three", "through", "throughout", "thru", "thus", "together", "took", "toward", "towards", "tried", "tries", "truly", "trying", "twice", "under", "unfortunately", "unless", "unlikely", "until", "unto", "upon", "used", "useful", "uses", "using", "usually", "value", "various", "very", "want", "wants", "wasn't", "we'd", "we'll", "we're", "we've", "welcome", "well", "went", "were", "weren't", "what", "what's", "whatever", "when", "whence", "whenever", "where", "where's", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who's", "whoever", "whole", "whom", "whose", "will", "willing", "wish", "with", "within", "without", "won't", "wonder", "would", "wouldn't", "you'd", "you'll", "you're", "you've", "your", "yours", "yourself", "yourselves", "zero"];
            $text = str_replace("\n", " ", $text);
            $textparts = explode(" ", strtolower($text));
            $validword = 0;
            foreach ($textparts as $k => $v) {
                if (in_array($v, $ignorewords) || strlen($textparts[$k]) <= 3 || $validword >= 100) {
                    unset($textparts[$k]);
                } else {
                    $validword++;
                }
            }
            $kbarticles = self::getKBAutoSuggestionsQuery("title", $textparts, 5);
            if (count($kbarticles) < 5) {
                $numleft = 5 - count($kbarticles);
                $kbarticles = array_merge($kbarticles, self::getKBAutoSuggestionsQuery("article", $textparts, $numleft, $kbarticles));
            }
        }
        return $kbarticles;
    }

    public static function getKBAutoSuggestionsQuery($field, $textparts, $limit, $existingkbarticles = "")
    {
        $kbarticles = [];
        $where = "";
        foreach ($textparts as $textpart) {
            $where .= "{$field} LIKE '%" . \App\Helpers\Database::db_escape_string($textpart) . "%' OR ";
        }
        $where = !$where ? "id!=''" : substr($where, 0, -4);
        if (is_array($existingkbarticles)) {
            $existingkbids = array_map('intval', array_column($existingkbarticles, 'id'));
            $where = "({$where})";
            if (count($existingkbids) > 0) {
                $where .= " AND id NOT IN (" . \App\Helpers\Database::db_build_in_array($existingkbids) . ")";
            }
        }
        $result = \App\Models\Knowledgebase::whereRaw($where)->orderBy("useful", "DESC")->limit($limit)->get();
        foreach ($result as $data) {
            $articleid = $data->id;
            $parentid = $data->parentid;
            if ($parentid) {
                $articleid = $parentid;
            }
            $result2 = DB::select(DB::raw("SELECT tblknowledgebaselinks.categoryid FROM tblknowledgebase INNER JOIN tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid INNER JOIN tblknowledgebasecats ON tblknowledgebasecats.id=tblknowledgebaselinks.categoryid WHERE (tblknowledgebase.id={$articleid} OR tblknowledgebase.parentid={$articleid}) AND tblknowledgebasecats.hidden=''"));
            foreach ($result2 as $data) {
                $categoryid = $data->categoryid;
                if ($categoryid) {
                    $result2 = DB::select(DB::raw("SELECT * FROM tblknowledgebase WHERE (id={$articleid} OR parentid={$articleid}) AND (language='" . \App\Helpers\Database::db_escape_string(session('Language')) . "' OR language='') ORDER BY language DESC"));
                    $data = $result2[0];
                    $title = $data->title;
                    $article = $data->article;
                    $views = $data->views;
                    $kbarticles[] = ["id" => $articleid, "category" => $categoryid, "title" => $title, "article" => self::ticketsummary($article), "text" => $article];
                }
            }
        }
        return $kbarticles;
    }

    public static function ticketsummary($text, $length = 100)
    {
        $tail = "...";
        $text = strip_tags($text);
        $txtl = strlen($text);
        if ($length < $txtl) {
            for ($i = 1; $text[$length - $i] != " "; $i++) {
                if ($i == $length) {
                    return substr($text, 0, $length) . $tail;
                }
            }
            $text = substr($text, 0, $length - $i + 1) . $tail;
        }
        return $text;
    }

    public static function checkTicketAttachmentSize()
    {
        $postMaxSizeIniSetting = ini_get("post_max_size");
        $postMaxSize = self::convertIniSize($postMaxSizeIniSetting);
        $contentLength = (int) $_SERVER["CONTENT_LENGTH"];
        if (!$contentLength) {
            return true;
        }
        if ($postMaxSize < $contentLength) {
            LogActivity::Save(sprintf("A ticket attachment submission of %d bytes total was rejected due to PHP post_max_size setting being too small (%s or %d bytes).", $contentLength, $postMaxSizeIniSetting, $postMaxSize));
            return false;
        }
        $uploadMaxFileSizeIniSetting = ini_get("upload_max_filesize");
        $uploadMaxFileSize = self::convertIniSize($uploadMaxFileSizeIniSetting);
        if (isset($_FILES)) {
            $fileTooLarge = is_array($_FILES["attachments"]["error"]) ? in_array(UPLOAD_ERR_INI_SIZE, $_FILES["attachments"]["error"]) : $_FILES["attachments"]["error"] == UPLOAD_ERR_INI_SIZE;
            if ($fileTooLarge) {
                LogActivity::Save(sprintf("A ticket attachment was rejected due to PHP upload_max_filesize setting being too small (%s or %d bytes).", $uploadMaxFileSizeIniSetting, $uploadMaxFileSize));
                return false;
            }
        }
        return true;
    }

    public static function convertIniSize($size)
    {
        $multipliers = ["K" => 1024, "M" => 1024 * 1024, "G" => 1024 * 1024 * 1024];
        $mod = strtoupper(substr($size, -1, 1));
        $mult = $multipliers[$mod] ?? 1;
        if ($mult > 1) {
            $size = (int) substr($size, 0, -1);
        }
        return $size * $mult;
    }

    public static function validateAdminTicketAccess($ticketid)
    {
        $auth = Auth::guard('admin')->user();
        $adminid = $auth ? $auth->id : 0;
        $data = \App\Models\Ticket::where("id", $ticketid);
        $id = $data->value("id");
        $deptid = $data->value("did");
        $flag = $data->value("flag");
        $mergedTicketId = $data->value("merged_ticket_id");
        if (!$id) {
            return "invalidid";
        }
        if (!in_array($deptid, self::getAdminDepartmentAssignments()) && !\App\Helpers\AdminFunctions::checkPermission("Access All Tickets Directly", true)) {
            return "deptblocked";
        }
        if ($flag && $flag != $adminid && !\App\Helpers\AdminFunctions::checkPermission("View Flagged Tickets", true) && !\App\Helpers\AdminFunctions::checkPermission("Access All Tickets Directly", true)) {
            return "flagged";
        }
        if ($mergedTicketId) {
            return "merged" . $mergedTicketId;
        }
        return false;
    }

    public static function getAdminDepartmentAssignments()
    {
        $auth = Auth::guard('admin')->user();
        $adminid = $auth ? $auth->id : 0;
        static $DepartmentIDs = [];
        if (count($DepartmentIDs)) {
            return $DepartmentIDs;
        }
        $data = \App\Models\Admin::where("id", $adminid);
        $supportdepts = explode(",", $data->value("supportdepts"));
        $DepartmentIDs = array_filter($supportdepts);
        return $DepartmentIDs;
    }

    public static function AdminRead($tid)
    {
        $auth = Auth::guard('admin')->user();
        $adminid = $auth ? $auth->id : 0;
        $data = \App\Models\Ticket::where("id", $tid);
        $adminread = $data->value("adminunread");
        // $adminreadarray = $adminread ? explode(",", $adminread) : [];
		$adminreadarray = $adminread ? $adminread : array();
        if (!in_array($adminid, $adminreadarray)) {
            $adminreadarray[] = $adminid;
            \App\Models\Ticket::where("id", $tid)->update(["adminunread" => implode(",", $adminreadarray)]);
        }
    }
	
}



