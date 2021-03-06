<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAppeal;
use App\Models\Appeal;
use App\Models\Applicant;
use App\Models\Document;
use App\Models\Participant;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class AppealController extends Controller
{
    public function create(Request $request): JsonResource
    {
        $esiaLogin = $request->post('esia_login') ?? null;
        $esiaPassword = $request->post('esia_password') ?? null;
        $selemiumUrl = $request->post('selenium_url') ?? null;

        $appealType = $request->post('appeal_type');
        $birthplace = $request->post('birthplace');
        $courtRegion = $request->post('court_region');
        $courtJudiciary = $request->post('court_judiciary');

        $proxyFiles = $request->file('proxy_files') ?? null;
        $essenceFiles = $request->file('essence_files') ?? null;
        $attachmentFiles = $request->file('attachment_files') ?? null;
        $paymentFiles = $request->file('payment_files') ?? null;

        $applicants = json_decode($request->post('applicants'), true) ?? null;
        $participants = json_decode($request->post('participants'), true) ?? null;

        try {
            $appeal = new Appeal();
            $appeal->esia_login = $esiaLogin;
            $appeal->esia_password = $esiaPassword;
            $appeal->selenium_url = $selemiumUrl;
            $appeal->type = $appealType;
            $appeal->birthplace = $birthplace;
            $appeal->court_region = $courtRegion;
            $appeal->court_judiciary = $courtJudiciary;

            if ($appeal->save()) {
                if (isset($applicants)) {
                    foreach ($applicants as $item) {
                        $applicant = new Applicant($item);
                        $applicant->appeal_id = $appeal->id;
                        $applicant->save();
                    }
                }

                if (isset($participants)) {
                    foreach ($participants as $item) {
                        $participant = new Participant($item);
                        $participant->appeal_id = $appeal->id;
                        $participant->save();
                    }
                }

                if (isset($proxyFiles)) {
                    $this->storeDocuments($proxyFiles, Document::TYPE_PROXY, $appeal->id);
                }

                if (isset($essenceFiles)) {
                    $this->storeDocuments($essenceFiles, Document::TYPE_ESSENCE, $appeal->id);
                }

                if (isset($attachmentFiles)) {
                    $this->storeDocuments($attachmentFiles, Document::TYPE_ATTACHMENT, $appeal->id);
                }

                if (isset($paymentFiles)) {
                    $this->storeDocuments($paymentFiles, Document::TYPE_PAYMENT, $appeal->id);
                }

                $appeal->status = Appeal::STATUS_PROCESSING;
                $appeal->save();
                $job = (new ProcessAppeal($appeal));
                dispatch($job);

                return new JsonResource([
                    'appeal_id' => $appeal->id,
                ]);
            }

        } catch (\Throwable $exception) {
            return new JsonResource([
                'message' => $exception->getMessage(),
                'code' => 422,
            ]);
        }
    }

    public function get(Request $request): JsonResponse
    {
        $appealId = $request->get('appeal_id');
        if (isset($appealId) && !empty($appealId)) {
            try {
                $appeal = Appeal::findOrFail(['id' => $appealId]);
                return new JsonResponse($appeal);
            } catch (\Throwable $exception) {
                return new JsonResponse([
                    'message' => $exception->getMessage(),
                    'code' => 422
                ]);
            }
        }

        return new JsonResponse([
            'message' => '?????????????????????? appeal_id',
            'code' => 400
        ]);
    }

    public function createAppeal(
        string $esia_login = '',
        string $esia_password = '',
        string $selemium_url = '',
        string $appeal_type = '',
        string $birthplace = '',
        string $court_region = '',
        string $court_judiciary = '',
        string $applicants = '',
        string $participants = '',
        array  $proxy_files = [],
        array  $essence_files = [],
        array  $attachment_files = [],
        array  $payment_files = []
    )
    {

        $args = get_defined_vars();
        $params['multipart'] = [];

        foreach ($args as $key => $value) {
            if (gettype($value) == 'string') {
                $params['multipart'][] = [
                    'name' => $key,
                    'contents' => $value
                ];
            } else if (gettype($value) == 'array') {
                foreach ($value as $k => $file) {
                    $params['multipart'][] = [
                        'name' => $key . '[]',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $file->getClientOriginalName()
                    ];
                }
            }
        }

        $client = new Client();
        $res = $client->request(
            'POST',
            'http://justice.loc/api/appeal?XDEBUG_SESSION_START=PHPSTORM',
            $params,

        );

        return $res->getBody();
    }

    private function storeDocuments($files, $type, $appealId)
    {
        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $path = $file->store($appealId, $type);
            $pageCount = $this->getDocumentPagesCount($file);

            $document = new Document();
            $document->name = $fileName;
            $document->appeal_id = $appealId;
            $document->path = $path;
            $document->type = $type;
            $document->page_count = $pageCount;

            if (!$document->save()) {
                throw new \Exception('???? ?????????????? ?????????????????? ????????????????');
            }
        }
    }

    private function getDocumentPagesCount($filename): int
    {
        $count = 0;
        $fp = fopen($filename, 'r');
        if ($fp) {
            $count = 0;
            while (!feof($fp)) {
                $line = fgets($fp, 255);
                if (preg_match('|/Count [0-9]+|', $line, $matches)) {
                    preg_match('|[0-9]+|', $matches[0], $matches2);
                    if ($count < $matches2[0]) {
                        $count = trim($matches2[0]);
                    }
                }
            }
            fclose($fp);
        }


        return $count;
    }
}
