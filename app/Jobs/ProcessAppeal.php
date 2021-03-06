<?php

namespace App\Jobs;

use App\Models\Appeal;
use App\Models\Document;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Facebook\WebDriver\WebDriverSelect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class ProcessAppeal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Экземпляр обращения
     *
     * @var Appeal
     */
    protected $appeal;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Appeal $appeal)
    {
        $this->appeal = $appeal;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        $serverUrl = $this->appeal->selenium_url;
        $desiredCapabilities = DesiredCapabilities::chrome();
        $chromeOptions = new ChromeOptions();
        $desiredCapabilities->setVersion("96.0");
        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        $desiredCapabilities->setCapability('enableVNC', true);

        $driver = RemoteWebDriver::create($serverUrl, $desiredCapabilities);
        $driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));
        $driver->manage()->window()->setPosition(new WebDriverPoint(2, 2));

        try {
            $driver->get("https://ej.sudrf.ru/");

            $driver->manage()->timeouts()->implicitlyWait(5);

            $loginButton = $driver->findElement(
                WebDriverBy::cssSelector('#login-link a')
            );

            $loginButton->click();

            sleep(1);

            $iAgreeCheckbox = $driver->findElement(
                WebDriverBy::cssSelector('#iAgree')
            );
            $iAgreeCheckbox->click();

            if ($iAgreeCheckbox->isSelected()) {
                $esiaLogin = $driver->findElement(
                    WebDriverBy::cssSelector('.esiaLogin')
                );

                $esiaLogin->click();
            }

            $esiaLoginInput = $driver->findElement(
                WebDriverBy::cssSelector('#login')
            );

            $esiaLoginPassword = $driver->findElement(
                WebDriverBy::cssSelector('#password')
            );

            $esiaLoginInput->sendKeys(["value" => $this->appeal->esia_login]);
            $esiaLoginPassword->sendKeys(["value" => $this->appeal->esia_password]);

            $driver->findElement(
                WebDriverBy::cssSelector('#loginByPwdButton')
            )->click();
            sleep(1);
            try {
                $driver->findElement(
                    WebDriverBy::xpath('//span[contains(text(), "Частное лицо")]')
                )->click();
                sleep(1);
            } catch (\Throwable $exception) {

            }
            $navBarMenu = $driver->findElement(
                WebDriverBy::xpath('//a[contains(@href, "/appeal/")]')
            );

            $navBarMenu->click();
            sleep(1);

            if ($this->appeal->type === Appeal::TYPE_ADMINISTRATIVE) {
                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "service-appeal")][1]')
                )->click();
                sleep(1);

                $driver->findElement(
                    WebDriverBy::xpath('//a[contains(text(), "Административное исковое заявление")]')
                )->click();
                sleep(1);

//            $surname = $this->findElementSafe($driver,
//                WebDriverBy::cssSelector('#Surname')
//            );
            } else {
                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "service-appeal")][2]')
                )->click();
                sleep(1);

                $driver->findElement(
                    WebDriverBy::xpath('//a[contains(text(), "Исковое заявление")]')
                )->click();
                sleep(1);

//            $surname = $this->findElementSafe($driver,
//                WebDriverBy::cssSelector('#Surname')
//            );
            }

            $driver->findElement(
                WebDriverBy::xpath('//button[contains(@name, "Method")][1]')
            )->click();

//        if ($surname === null) {
            $driver->findElement(
                WebDriverBy::xpath('//button[contains(@name, "Method")][2]')
            )->click();
//        }
            sleep(1);

            $this->deleteUploadedFiles($driver);
            sleep(2);

            $driver->findElement(
                WebDriverBy::cssSelector('#BirthPlace')
            )->sendKeys($this->appeal->birthplace);
            sleep(5);


            // Документ, подтверждающий полномочия
            $proxyDocument = $this->appeal
                ->documents()
                ->where(['type' => Document::TYPE_PROXY])
                ->where(['appeal_id' => $this->appeal->id])
                ->first();

            $this->fillProxyDocument($driver, storage_path('proxy') . '/' . $proxyDocument->path, $proxyDocument->page_count);
            sleep(2);

            $this->fillApplicantData($driver, $this->appeal->applicant()->first());

            // Добавить участников
            $this->fillParticipantsData($driver, $this->appeal->participants()->get());

            sleep(3);

            // Заполнить данные суда
            $this->fillCourtData($driver, $this->appeal->court_region, $this->appeal->court_judiciary);

            // Добавить файл "Суть заявления"
            $essenceFile = $this->appeal
                ->documents()
                ->where(['type' => Document::TYPE_ESSENCE])
                ->where(['appeal_id' => $this->appeal->id])
                ->first();
            $this->fillEssenceDocument($driver, storage_path('essence') . '/' . $essenceFile->path, $essenceFile->page_count);

            // Добавить файлы "Приложения к заявлению"
            $attachments = $this->appeal
                ->documents()
                ->where(['type' => Document::TYPE_ATTACHMENT])
                ->where(['appeal_id' => $this->appeal->id]);

            $this->fillAttachmentDocuments($driver, $attachments);

            sleep(5);

            //Поставить галочку "Квитанция об уплате государственной пошлины"
            $driver->findElement(
                WebDriverBy::cssSelector('#Tax_Free')
            )->click();

            // Добавить файл квитанции об уплате гос. пошлины
            $paymentDocument = $this->appeal
                ->documents()
                ->where(['type' => Document::TYPE_PAYMENT])
                ->where(['appeal_id' => $this->appeal->id])
                ->first();
            $this->fillPaymentDocument($driver, storage_path('payment') . '/' . $paymentDocument->path, $paymentDocument->page_count);

            sleep(3);

            // Сохранить заявление
            $driver->findElement(
                WebDriverBy::xpath('//button[contains(@title, "Сформируйте и отправьте обращение в суд")]')
            )->click();
            sleep(3);

            // Отправить заявление
            $driver->findElement(
                WebDriverBy::xpath('//button[contains(text(), "Отправить")]')
            )->click();


            $appealId = $driver->findElement(
                WebDriverBy::xpath('//label[contains(text(), "Номер")]/following::div[1]/div')
            )->getText();

            $this->appeal->status = Appeal::STATUS_PROCESSED;
            $this->appeal->external_id = $appealId;
            $this->appeal->save();

            $driver->quit();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
//            $this->appeal->status = Appeal::STATUS_FAILED;
//            $this->appeal->save();
//            $this->release(10);
//            $driver->quit();
        }
    }


    private function fillApplicantData(RemoteWebDriver $driver, $applicant)
    {
        try {
            $driver->findElement(
                WebDriverBy::xpath('//button[contains(text(), "Добавить заявителя")]')
            )->click();

            $driver->manage()->timeouts()->implicitlyWait(3);

            $driver->findElement(
                WebDriverBy::xpath('//button[contains(text(), "Юридическое лицо")]')
            )->click();


            $driver->findElement(WebDriverBy::cssSelector('#Company_Name'))->sendKeys($applicant->name);
            sleep(1);

            $driver->findElement(WebDriverBy::cssSelector('#Company_INN'))->sendKeys($applicant->inn);
            sleep(1);

            $proceduralStatus = new WebDriverSelect(
                $driver->findElement(
                    WebDriverBy::xpath('//select[contains(@id, "ProceduralStatus")]')
                )
            );
            $proceduralStatus->selectByVisibleText($applicant->procedural_status);
            sleep(3);

            $driver->findElement(WebDriverBy::cssSelector('#Company_OGRN'))->sendKeys($applicant->ogrn);
            sleep(1);

            $driver->findElement(WebDriverBy::cssSelector('#Company_KPP'))->sendKeys($applicant->kpp);
            sleep(1);

            $driver->findElement(WebDriverBy::cssSelector('#Address_Legal_Index'))->sendKeys($applicant->legal_zipcode);
            sleep(1);

            $driver->findElement(WebDriverBy::cssSelector('#Address_Legal_Address'))->sendKeys($applicant->legal_address);
            sleep(1);

            $driver->findElement(WebDriverBy::cssSelector('#Address_Physical_Index'))->sendKeys($applicant->location_zipcode);
            sleep(1);

            $driver->findElement(WebDriverBy::cssSelector('#Address_Physical_Address'))->sendKeys($applicant->location_address);
            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//input[contains(@title, "Введите адрес электронной почты представляемого лица")]')
            )->sendKeys($applicant->email);
            sleep(1);
            //   dd();
            $driver->findElement(
                WebDriverBy::xpath('//input[contains(@title, "Введите номер телефона представляемого лица")]')
            )->sendKeys($applicant->phone);
            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Сохранить")]')
            )->click();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }
    }

    private function fillProxyDocument(RemoteWebDriver $driver, string $file = '', $pageCount)
    {
        $driver->manage()->timeouts()->implicitlyWait(3);

        $addProxyFileBtn = $this->findElementSafe(
            $driver,
            WebDriverBy::xpath('//label[contains(@for, "Document")]/following-sibling::div/div/button')
        );

        if ($addProxyFileBtn !== null) {
            $addProxyFileBtn->click();

            sleep(2);

            $proxyFileInput = $driver->findElement(
                WebDriverBy::xpath('//input[contains(@name, "ajax_upload_file_input")]')
            );

            // Добавить дкумент, подтверждающий полномочия
            $proxyFileInput->setFileDetector(new LocalFileDetector());
            $proxyFileInput->sendKeys($file);

            sleep(5);

            $driver->findElement(
                WebDriverBy::xpath('//input[contains(@name, "pageNum")]')
            )->sendKeys($pageCount);

            sleep(2);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Добавить")]')
            )->click();
        }
    }

    private function fillEssenceDocument(RemoteWebDriver $driver, string $file = '', $pageCount)
    {
        $driver->manage()->timeouts()->implicitlyWait(3);

        $addEssenceFileBtn = $this->findElementSafe(
            $driver,
            WebDriverBy::xpath('//label[contains(@for, "Appeal_Content_File")]/following-sibling::div/div/button')
        );

        if ($addEssenceFileBtn !== null) {
            $addEssenceFileBtn->click();

            $essenceFileInput = $driver->findElement(
                WebDriverBy::xpath('//input[contains(@name, "ajax_upload_file_input")]')
            );

            // Добавить дкумент, подтверждающий полномочия
            $essenceFileInput->setFileDetector(new LocalFileDetector());
            $essenceFileInput->sendKeys($file);

            sleep(5);

            $driver->findElement(
                WebDriverBy::xpath('//input[contains(@name, "pageNum")]')
            )->sendKeys($pageCount);

            sleep(2);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Добавить")]')
            )->click();
        }
    }

    private function fillCourtData(RemoteWebDriver $driver, string $courtRegion, string $courtJudiciary)
    {
        $driver->manage()->timeouts()->implicitlyWait(3);

        $chooseCourt = $driver->findElement(
            WebDriverBy::xpath('//button[contains(text(), "Выбрать суд")]')
        );
        $chooseCourt->click();

        $driver->manage()->timeouts()->implicitlyWait(3);

        $currentRegion = new WebDriverSelect(
            $driver->findElement(
                WebDriverBy::xpath('//select[contains(@name, "currentRegion")]')
            )
        );

        $currentRegion->selectByVisibleText($courtRegion);

        $currentCourt = new WebDriverSelect(
            $driver->findElement(
                WebDriverBy::xpath('//select[contains(@name, "currentCourt")]')
            )
        );

        $currentCourt->selectByValue($courtJudiciary);

        sleep(3);

        $driver->findElement(
            WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Сохранить")]')
        )->click();
    }

    private function fillParticipantsData(RemoteWebDriver $driver, $participants)
    {
        foreach ($participants as $participant) {
            $driver->findElement(
                WebDriverBy::xpath('//button[contains(text(), "Добавить участника")]')
            )->click();

            $driver->manage()->timeouts()->implicitlyWait(3);

            $driver->findElement(
                WebDriverBy::xpath('//button[contains(text(), "Физическое лицо")]')
            )->click();
            sleep(1);


            $proceduralStatus = new WebDriverSelect(
                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/select[contains(@id, "ProceduralStatus")]')
                )
            );
            $proceduralStatus->selectByVisibleText($participant->procedural_status);
            sleep(3);


            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Surname")]')
            )->sendKeys($participant->last_name);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Name")]')
            )->sendKeys($participant->first_name);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Patronymic")]')
            )->sendKeys($participant->middle_name);


            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "BirthDate")]')
            )->sendKeys($participant->birthdate);


            if ($participant->sex === 'male') {
                // Если пол мужской
                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/div/button[1]')
                )->click();
            } else {
                // Если пол женский
                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/div/button[2]')
                )->click();
            }

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "BirthPlace")]')
            )->sendKeys($participant->birthplace);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Permanent_Index")]')
            )->sendKeys($participant->registration_zipcode);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Permanent_Address")]')
            )->sendKeys($participant->registration_address);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Actual_Index")]')
            )->sendKeys($participant->resident_zipcode);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Actual_Address")]')
            )->sendKeys($participant->resident_address);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Snils")]')
            )->sendKeys($participant->snils);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "INN")]')
            )->sendKeys($participant->inn);

            sleep(1);

            // Тип паспорта(РФ или иностранный)
            $identityType = new WebDriverSelect(
                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/select[contains(@id, "Passport_Type")]')
                )
            );
            $identityType->selectByVisibleText($participant->identity_type);
            sleep(1);


            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Passport_Series")]')
            )->sendKeys($participant->passport_series);
            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Passport_Number")]')
            )->sendKeys($participant->passport_number);
            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Passport_IssueDate")]')
            )->sendKeys($participant->passport_issued_date);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/input[contains(@id, "Passport_IssuedBy")]')
            )->sendKeys($participant->passport_issued_by);
            sleep(1);


            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Driving_License_Series")]')
            )->sendKeys($participant->drivers_license_series);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Driving_License_Number")]')
            )->sendKeys($participant->drivers_license_number);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "SRTS_Series")]')
            )->sendKeys($participant->vehicle_series);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "SRTS_Number")]')
            )->sendKeys($participant->vehicle_number);

            sleep(1);


            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Email")]')
            )->sendKeys($participant->email);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Phone")]')
            )->sendKeys($participant->phone);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Сохранить")]')
            )->click();

            sleep(3);
        }
    }

    private function fillAttachmentDocuments(RemoteWebDriver $driver, $attachments)
    {
        foreach ($attachments as $attachment) {
            $driver->manage()->timeouts()->implicitlyWait(3);

            $addAttachmentFileBtn = $this->findElementSafe(
                $driver,
                WebDriverBy::xpath('//label[contains(@for, "Appeal_Attachments")]/following-sibling::div/div/button')
            );

            if ($addAttachmentFileBtn !== null) {
                $addAttachmentFileBtn->click();

                $attachmentFileInput = $driver->findElement(
                    WebDriverBy::xpath('//input[contains(@name, "ajax_upload_file_input")]')
                );

                // Добавить дкумент
                $attachmentFileInput->setFileDetector(new LocalFileDetector());
                $attachmentFileInput->sendKeys(storage_path('attachment') . '/' . $attachment->path);

                // Подождать 3 секунд для прогрузки файла
                sleep(3);

                // Указать количество страниц
                $driver->findElement(
                    WebDriverBy::xpath('//input[contains(@name, "pageNum")]')
                )->sendKeys($attachment->page_count);

                $driver->findElement(
                    WebDriverBy::xpath('//input[contains(@name, "comment")]')
                )->sendKeys(["value" => $attachment->name]);

                sleep(2);

                $driver->findElement(
                    WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Добавить")]')
                )->click();
            }
        }
    }

    private function fillPaymentDocument(RemoteWebDriver $driver, $file, $pageCount)
    {
        sleep(3);

        $addPaymentFileBtn = $this->findElementSafe(
            $driver,
            WebDriverBy::xpath('(//*[contains(text(), "Добавить файл")])[last()]')
        );

        if ($addPaymentFileBtn !== null) {
            $addPaymentFileBtn->click();

            $paymentFileInput = $driver->findElement(
                WebDriverBy::xpath('//input[contains(@name, "ajax_upload_file_input")]')
            );

            // Добавить дкумент
            $paymentFileInput->setFileDetector(new LocalFileDetector());
            $paymentFileInput->sendKeys($file);

            // Подождать 5 секунд для прогрузки файла
            sleep(5);

            // Указать количество страниц
            $driver->findElement(
                WebDriverBy::xpath('//input[contains(@name, "pageNum")]')
            )->sendKeys($pageCount);

            sleep(2);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Добавить")]')
            )->click();
        }
    }

    private function deleteUploadedFiles(RemoteWebDriver $driver)
    {
        $files = $driver->findElements(
            WebDriverBy::xpath('//a[contains(text(), "Удалить файл")]')
        );

        foreach ($files as $file) {
            $file->click();
        }
    }

    private function findElementSafe(RemoteWebDriver $driver, WebDriverBy $by)
    {
        try {
            return $driver->findElement($by);
        } catch (NoSuchElementException $exception) {
            return null;
        }
    }
}
