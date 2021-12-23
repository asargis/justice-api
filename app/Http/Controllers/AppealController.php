<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppealRequest;
use App\Jobs\ProcessAppeal;
use App\Models\Appeal;
use App\Models\Applicant;
use App\Models\Document;
use App\Models\Participant;
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

                ProcessAppeal::dispatch($appeal);
            }

        } catch (\Throwable $exception) {
            return new JsonResource([
                'message' => $exception->getMessage(),
                'code' => 422,
            ]);
        }


        return new JsonResource([
            'message' => 'ok...',
        ]);
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
                throw new \Exception('Не удалось сохранить документ');
            }
        }
    }

    private function getDocumentPagesCount($document): int
    {
        $im = new \Imagick();
        $im->pingImage($document);
        return $im->getNumberImages();
    }
}