<?php

namespace Modules\Addons\PrivateNsRegistrar\Http\Controllers;

use App\Models\Client;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use App\Models\Domain;
use App\Models\Modbox;
use App\Models\PrivateNsDocument;

class PrivateNsRegistrarController extends Controller
{
    public static  $dir;
    protected $dir_read;


    function __construct()
    {
       $this->dir_read = 'https://' . $_SERVER['SERVER_NAME'] . '/Files/';
        self::$dir = '/home/clientgudang/domains/client.gudangssl.id/public_html/Files';
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Display the module configuration.
     */
    public function config()
    {
        return [
            'name' => 'PrivateNS Registrar',
            'description' => 'Module ini digunakan untuk upload dokumen sebagai persyaratan kelengkapan domain',
            'author' => 'CBMS Developer - Fajar',
            'language' => 'english',
            'version' => '1.0',
            'fields' => [],
        ];
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Render the module output based on the requested page.
     */
    public function output()
    {
        $page = request('page', 'index');
        $userid = request('userid');
        $format = request('format', 'html');

        $respond = function ($data, $view) use ($format) {
            return $format === 'json'
                ? response()->json(['status' => 'success', 'data' => $data])
                : view($view, $data);
        };

        switch ($page) {
            case 'files':
                return $this->fetchFiles($respond);

            case 'document_client':
                return $this->handleDocumentClientPage($userid, $format, $respond);

            default:
                return $this->handleIndexPage($respond);
        }
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Handle the 'document_client' page request.
     */
    protected function handleDocumentClientPage($userid, $format, $respond)
    {
        if (!$userid) {
            return $format === 'json'
                ? response()->json(['status' => 'error', 'message' => 'User ID not specified.'])
                : redirect()->back()->with('error', 'User ID not specified.');
        }

        $documents = DB::table('mod_box')
            ->select('file', 'type')
            ->where('userid', $userid)
            ->get();

        return $respond(compact('documents'), 'privatensregistrar::detail_document');
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Handle the 'index' page request.
     */
    protected function handleIndexPage($respond)
    {
        $allDocuments = $this->fetchAllDocuments();
        $approvalDocuments = $this->fetchApprovalDocuments();

        /**
        \Log::info('All Documents:', $allDocuments->toArray());
        \Log::info('Approval Documents:', $approvalDocuments->toArray());
        */

        return $respond([
            'all_documents' => $allDocuments,
            'approval' => $approvalDocuments,
        ], 'privatensregistrar::index');
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Fetch all documents from tblclients with document count.
     */
    protected function fetchAllDocuments()
    {
        return Client::query()
            ->leftJoinSub(
                DB::table('mod_box')
                    ->select('userid', DB::raw('COUNT(*) as jumlah'))
                    ->groupBy('userid'),
                'doc_counts',
                'tblclients.id',
                '=',
                'doc_counts.userid'
            )
            ->select('tblclients.id', 'tblclients.firstname', 'tblclients.companyname', 'tblclients.email', 'tblclients.phonenumber', 'doc_counts.jumlah')
            ->get();
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Fetch documents that need approval by checking 'syarat' JSON field for status 0.
     */
    public function fetchApprovalDocuments()
    {
        $approvalDocuments = DB::table('privatensdocument')
        ->join('tblhosting', 'tblhosting.domain', '=', 'privatensdocument.domain')
        ->join('tblclients', 'tblclients.id', '=', 'tblhosting.userid')
        ->select('privatensdocument.syarat', 'tblclients.firstname as client_name', 'tblclients.email as client_email', 'tblhosting.domain')
        ->where('privatensdocument.syarat', 'LIKE', '%"status":0%')
        ->get()
        ->map(function ($item) {
            $syarat = json_decode($item->syarat, true);
            $totalWaiting = collect($syarat)->filter(fn($data) => $data['status'] === 0)->count();

            return (object) [
                'domain' => $item->domain,
                'client_name' => $item->client_name,
                'client_email' => $item->client_email,
                'file' => $totalWaiting,
            ];
        });

        // Debugging tambahan
        if ($approvalDocuments->isEmpty()) {
            \Log::info('fetchApprovalDocuments: No documents requiring approval found.');
        } else {
            \Log::info('fetchApprovalDocuments: Found documents:', $approvalDocuments->toArray());
        }

        return $approvalDocuments;
    }

    public function activate()
    {
        return [
            'status' => 'success',
            'description' => 'Module enabled'
        ];
    }

    public function deactivate()
    {
        return [
            'status' => 'success',
            'description' => 'Module has been disabled.'
        ];
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Handle TLD synchronization.
     */
    public function syncTLD(Request $request)
    {

        \Log::info('Request received for syncTLD:', $request->all());

        // Contoh validasi sederhana
        if (!$request->has('_token')) {
            return response()->json(['error' => true, 'message' => 'CSRF token missing'], 400);
        }

        try {
            $config = PrivateNsRegistrarHelper::getConfig();

            if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['api_url'])) {
                return response()->json(['error' => true, 'message' => 'Konfigurasi API tidak lengkap.'], 400);
            }

            $authData = [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ];

            $authResponse = Http::asForm()->post($config['api_url'] . '/oauth/token', $authData);

            if ($authResponse->failed()) {
                return response()->json(['error' => true, 'message' => 'Gagal melakukan autentikasi.'], 400);
            }

            $accessToken = $authResponse->json()['access_token'] ?? null;

            if (!$accessToken) {
                return response()->json(['error' => true, 'message' => 'Token akses tidak ditemukan.'], 400);
            }

            $syncData = [
                'oauth_client_id' => $config['client_id'],
                'kurs_id' => 1,
                'product_type_id' => 1,
                'period' => 1,
                'character' => 0,
            ];

            $syncResponse = Http::withToken($accessToken)
                ->asForm()
                ->post($config['api_url'] . '/rest/v2/domain/list/pricing/reseller', $syncData);

            if ($syncResponse->failed()) {
                return response()->json(['error' => true, 'message' => $syncResponse->json()['message'] ?? 'Gagal melakukan sinkronisasi.'], 400);
            }

            return response()->json(['error' => false, 'message' => 'Sinkronisasi TLD berhasil.']);
        } catch (\Exception $e) {
            \Log::error('Error syncing TLD:', ['error' => $e->getMessage()]);
            return response()->json(['error' => true, 'message' => 'Terjadi kesalahan saat sinkronisasi.'], 500);
        }
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Fetch domain document for a specific domain and render it in a view.
     */
    public function fetchDomainDocument(Request $request)
    {
        $domain = $request->input('domain');
        \Log::info('Domain received:', compact('domain'));

        $document = DB::table('privatensdocument')->where('domain', $domain)->first();

        if (!$document) {
            return response('<p class="text-center">Tidak ada dokumen untuk ditampilkan.</p>', 404);
        }

        $syarat = json_decode($document->syarat, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('Invalid JSON in syarat column:', ['error' => json_last_error_msg()]);
            return response('<p class="text-center">Format data tidak valid.</p>', 500);
        }

        $pendingDocuments = collect($syarat)->filter(fn($doc) => $doc['status'] === 0)->toArray();

        return view('privatensregistrar::partials.list_image', [
            'documents' => $pendingDocuments,
            'domain' => $domain,
            'dir_read' => $this->dir_read,
        ]);
    }

    /**
     * Author: Fajar Habib Zaelani
     * Date: 15 Novemmber 2024
     * For: Process document approval or rejection.
     */
    public function processDocument(Request $request)
    {
        $domain = $request->input('domain');
        $key = $request->input('key');
        $status = $request->input('status');
        $note = $request->input('ket');

        $document = DB::table('privatensdocument')->where('domain', $domain)->first();

        if (!$document) {
            return response()->json(['code' => 4001, 'msg' => 'Dokumen tidak ditemukan.'], 404);
        }

        $syarat = json_decode($document->syarat, true);

        if (!isset($syarat[$key])) {
            return response()->json(['code' => 4001, 'msg' => 'Dokumen tidak valid.'], 400);
        }

        $syarat[$key]['status'] = $status;
        $syarat[$key]['ket'] = $note;

        DB::table('privatensdocument')
            ->where('domain', $domain)
            ->update(['syarat' => json_encode($syarat)]);

        return response()->json(['code' => 1000, 'msg' => 'Dokumen berhasil diproses.']);
    }

    public function test($params)
    {
        return response()->json(['Message' => 'You Entered tes Funtion in PrivateNSRegistrar']);
    }

    public function requirement($params)
    {

        $id = $params['userid'];

        return array(
            'data' => array(
                'id'       => $id,
                'domains'  => $this->myDomain($id),
                'document' => $this->listFile($id),
                'dir'      => $this->dir_read,
                'table'    => $this->documentData($this->myDomain($id)),
            ),
        );
    }

    public function myDomain($id)
    {
        // Mengambil domain dari tblhosting
        $results = DB::table('tblhosting')
            ->where('userid', $id)
            ->get(['domain']);

        return $results->pluck('domain')->toArray();
    }

    public function documentData($client_domains)
    {

        $_document = [];

        foreach ($client_domains as $domain) {
            $detail = PrivateNsDocument::where('domain', $domain)->first();

            if ($detail) {
                $_syarat = json_decode($detail->syarat);
                $document_count = count($_syarat);

                $warning = array_filter($_syarat, function ($row) {
                    return $row->status == 0 || $row->status == 2;
                });

                $total_warning = count($warning);
                $status = $total_warning < 1 ? 'Ok' : 'Warning';

                $_document[] = [
                    'domain' => $domain,
                    'count' => $document_count,
                    'status' => $status,
                ];
            }
        }

        if (empty($_document)) {
            $_document[] = [
                'domain' => '',
                'count' => '',
                'status' => '',
            ];
        }
        return $_document;
    }

    public function domainDetail($params)
    {

        $domain = $params['domain'];
        $id = $params['userid'];

        // Check if the domain exists for the user in tblhosting
        $domainExists = DB::table('tblhosting')
            ->where('userid', $id)
            ->where('domain', $domain)
            ->exists();

        if (!$domainExists) {
            abort(404, 'Domain not found.');
        }

        // Retrieve document data
        $document = PrivateNsDocument::where('domain', $domain)->first();

        if (!$document) {
            abort(404, 'Document not found.');
        }

        $_syarat = json_decode($document->syarat, true);
        $keys = array_keys($_syarat);
        $detail = [];

        // Document syarat status
        $stat = ['Pending', 'Approved', 'Rejected'];

        foreach ($keys as $key) {
            $detail[] = [
                'type' => str_replace("_", " ", strtoupper($key)),
                'status' => $stat[$_syarat[$key]['status']],
                'ket' => $_syarat[$key]['ket'] ?? '-',
            ];
        }
        return $detail;
    }
    
    public function uploadImage($params)
    {
        $userId = Auth::id();
        $userEmail = Client::where('id', $userId)->value('email');

        if (!$params['file'] instanceof \Illuminate\Http\UploadedFile) {
            \Log::error('File is not an instance of UploadedFile.');
            return response()->json(['message' => 'Invalid file input.'], 400);
        }

        if (isset($params['file']) && $params['file'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $params['file'];

            $filename = uniqid() . '_' . $this->renameFile($file->getClientOriginalName());

            // Perbarui path tujuan
                $destinationPath = '/home/clientgudang/domains/client.gudangssl.id/public_html/Files';

            $filePath = $destinationPath . DIRECTORY_SEPARATOR . $filename;
            $file->move($destinationPath, $filename);

            $metadata = [
                'FILE' => [
                    'FileName' => basename($filePath),
                    'type' => mime_content_type($filePath) ?: 'unknown',
                ]
            ];

            $data = [
                'id' => uniqid(),
                'userid' => $userId,
                'comid' => md5($userEmail),
                'type' => mime_content_type($filePath) ?: 'unknown',
                'file' => $filename,
                'meta' => json_encode($metadata),
            ];

            ModBox::create($data);

            return response()->json([
                'message' => 'File uploaded successfully.',
                // Perbarui URL file
                'file_url' => 'https://' . $_SERVER['SERVER_NAME'] . '/my.hostingnvme.id/Files/' . $filename,
                'meta' => $metadata
            ]);
        }
        return response()->json([
            'message' => 'No file uploaded.'
        ], 400);
    }

    public function renameFile($file)
    {

        $name = explode(".", $file);
        $ext = $name[count($name) - 1];
        return uniqid() . "." . $ext;
    }

    public function saveImage($filePath, $rename)
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }

        $metadata = [
            'FILE' => [
                'FileName' => basename(self::$dir),
                'type' => mime_content_type(self::$dir) ?: 'unknown',
            ]
        ];

        $userId = Auth::id();
        $userEmail = Client::where('id', $userId)->value('email');

        $data = [
            'id' => uniqid(),
            'userid' => $userId,
            'comid' => md5($userEmail),
            'type' => $metadata['FILE']['type'] ?? 'N/A',
            'file' => $rename,
            'meta' => json_encode($metadata),
        ];

        $modBox = ModBox::create($data);
        return $modBox;
    }

    public function readMetadata($file)
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("File not found: $file");
        }

        $fileType = mime_content_type($file);
        if ($fileType === false) {
            $fileType = 'unknown';
        }

        $metadata = [
            'FILE' => [
                'FileName' => basename($file),
                'type' => $fileType
            ]
        ];

        return json_encode($metadata);
    }

    public function clientHome()
    {

        $userId = Auth::id();
        $mydomain = $this->myDomain($userId) ?? [];
        $domains = [];
        foreach ($mydomain as $value) {
            $domains[] = $value;
        }
        $id = $userId;

        $dir = $this->dir_read;


        return array(
            'data' => array(
                'domains'  => implode(', ', $domains),
                'document' => $this->listFile($id),
                'dir'      => $dir,
                'userid'   => $id,
            ),
        );
    }

    public function listFile($id)
    {
        if ($id === null) {
            return redirect()->to('https://' . request()->getHost());
        }

        $data = Modbox::where('userid', $id)->get();

        if ($data->isEmpty()) {
            return [];
        }

        return $data;
    }

    public function deleteImage($params)
    {
        $file = $params['file'];
        $id = Auth::id();

        $record = Modbox::where('file', $file)->where('userid', $id)->first();

        if ($record) {
            $record->delete();
            
                $filePath = '/home/clientgudang/domains/client.gudangssl.id/public_html/Files/' . $file;



            if (File::exists($filePath)) {
                File::delete($filePath);
                return response()->json([
                    'message' => 'File deleted successfully.',
                    'code' => 200
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to delete file.',
                    'code' => 500
                ]);
            }
        }
    }

    public function lookupTld($params)
    {
        $domain = $params['domain'];
        $id = $params['userid'];

        $datauser = DB::table('tblhosting')
            ->where('userid', $id)
            ->where('domain', $domain)
            ->exists();

        if (!$datauser) {
            return response('oops', 404);
        }

        // Return dokumen standar yang diperlukan untuk semua domain
        $doc = [
            'ktp' => 'KTP',
            'npwp' => 'NPWP'
        ];

        return response()->json($doc);
    }

    private function tld($domain)
    {
        $ex = explode('.', $domain);
        $count = count($ex);

        $path_tld = [];

        for ($i = 1; $i < $count; $i++) {
            $path_tld[] = $ex[$i];
        }

        return implode('.', $path_tld);
    }

    public function setDocument($params)
    {

        $userId = Auth::id();
        $domain = $params['domain'];
        $file = $params['file'];
        $type = $params['type'];
        $setAll = (int) ($params['set_all'] ?? 0);

        $datas = [
            "document_id"          => 1,
            "document_name"        => "Portal Upload",
            "document_description" => 'Portal Upload',
            "file"                 => '/home/clientgudang/domains/client.gudangssl.id/public_html/Files/' . $file,
            "domain"               =>  $domain
        ];
    
        $data = [
            'userid'    => $userId,
            'domain'    => $domain,
            'syarat'    => $this->syaratDomain($file, $domain, $type),
            'file_meta' => $this->metaDocument($file, $domain, $type, $userId),
        ];

        $record = PrivateNsDocument::where('domain', $domain)->where('userid', $userId)->first();

        if (!$record) {
            PrivateNsDocument::create($data);
            // $this->makeRequest($vars['apiurl'] . "/rest/v2/domain/upload/terms/portal", "POST", $auth->access_token, $datas);
        } else {
            // Update existing record
            $record->update($data);
            // $this->makeRequest($vars['apiurl'] . "/rest/v2/domain/upload/terms/portal", "POST", $auth->access_token, $datas);
        }

        if ($setAll == 1) {

            $idDomain = $this->getUserIdByDomain($domain);

            Modbox::where('file', $file)
                ->update([
                    'set_all' => 1,
                    'type' => $type,
                ]);

            Modbox::where('file', '!=', $file)
                ->where('type', $type)
                ->where('userid', $idDomain)
                ->update([
                    'set_all' => 0,
                ]);

            $this->updateAllDomainSyarat($domain, $type, $file);
        }

        return array(
            'data' => array(
                'status'  => 200,
                'message' => "Success set your document",
            ),
        );
    }

    private function syaratDomain($file, $domain, $type)
    {
        $syarat = [
            $type => [
                'file' => $file,
                'status' => 0,
            ],
        ];

        $record = PrivateNsDocument::where('domain', $domain)->first();
        if (!$record) {
            return json_encode($syarat);
        } else {
            $rec_syarat = json_decode($record->syarat, true) ?? [];

            if (array_key_exists($type, $rec_syarat)) {
                $rec_syarat[$type] = $syarat[$type];
            } else {
                $rec_syarat = array_merge($rec_syarat, $syarat);
            }

            $record->syarat = json_encode($rec_syarat);
            $record->save();

            return json_encode($rec_syarat);;
        }
    }

    private function metaDocument($file, $domain, $type, $userId)
    {
        $rec_meta = Modbox::where('file', $file)->first();

        if (!$rec_meta) {
            return json_encode([]);
        }

        $meta = [
            $type => [
                'file' => $file,
                'meta_data' => $rec_meta,
                'set_by' => $userId,
                'time' => time(),
            ],
        ];

        $record = PrivateNsDocument::where('domain', $domain)->first();

        if (!$record) {
            return json_encode($meta);
        } else {
            $record_meta = json_decode($record->file_meta, true) ?? []; 

            if (array_key_exists($type, $record_meta)) {
                $record_meta[$type] = $meta[$type];
            } else {
                $record_meta = array_merge($record_meta, $meta);
            }

            $record->file_meta = json_encode($record_meta);
            $record->save();

            return json_encode($record_meta);
        }
    }

    private function getUserIdByDomain($domain)
    {
        $hostingRecord = DB::table('tblhosting')
            ->where('domain', $domain)
            ->first();

        if ($hostingRecord) {
            return $hostingRecord->userid;
        }

        return null;
    }

    public function updateAllDomainSyarat($domain, $type, $file)
    {
        $userId = $this->getUserIdByDomain($domain);

        $record = PrivateNsDocument::where('domain', $domain)->first();

        if ($record) {
            $syarat = json_decode($record->syarat, true) ?? [];

            $syarat[$type] = [
                "file" => $file,
                "status" => 0,
                "ket" => "Replace by user doing set all"
            ];

            $record->syarat = json_encode($syarat);
            $record->save();

            return $this->insertNotRecordedDomain($userId, $type, $file);
        }

        return null;
    }

    private function insertNotRecordedDomain($userId, $type, $file)
    {
        $userId = Auth::id();
        
        // Ambil semua domain user dari tblhosting
        $domains = DB::table('tblhosting')
            ->where('userid', $userId)
            ->get();
        
        $insertedRecords = [];

        foreach ($domains as $domain) {
            $data = [
                'domain' => $domain->domain,
                'syarat' => $this->syaratDomain($file, $domain->domain, $type),
                'file_meta' => $this->metaDocument($file, $domain->domain, $type, $userId),
            ];

            $existingRecord = PrivateNsDocument::where('domain', $domain->domain)->first();

            if ($existingRecord) {
                $existingRecord->update($data);
            } else {
                $newRecord = PrivateNsDocument::create($data);
                $insertedRecords[] = $newRecord;
            }
        }

        return $insertedRecords;
    }
}