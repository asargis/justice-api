<?php

namespace App\Console\Commands;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Facebook\WebDriver\WebDriverSelect;
use Illuminate\Console\Command;

class TestSelenium extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'selenium:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Facebook\WebDriver\Exception\UnexpectedTagNameException
     */
    public function handle()
    {
        $serverUrl = 'http://localhost:4444/wd/hub';
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

            $esiaLoginInput->sendKeys(["value" => '+79134085008']);
            $esiaLoginPassword->sendKeys(["value" => '50Inru3hFreeRide.']);

            $driver->findElement(
                WebDriverBy::cssSelector('#loginByPwdButton')
            )->click();
            sleep(1);

            $navBarMenu = $driver->findElement(
                WebDriverBy::xpath('//a[contains(@href, "/appeal/")]')
            );

            $navBarMenu->click();
            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "service-appeal")][2]')
            )->click();
            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//a[contains(text(), "Исковое заявление")]')
            )->click();
            sleep(1);

            $surname = $this->findElementSafe($driver,
                WebDriverBy::cssSelector('#Surname')
            );

            if ($surname === null) {
                $driver->findElement(
                    WebDriverBy::xpath('//button[contains(@name, "Method")][2]')
                )->click();
            }
            sleep(1);

            $driver->findElement(
                WebDriverBy::cssSelector('#BirthPlace')
            )->sendKeys('г. Новокузнецк');
            sleep(5);

            $this->fillProxyDocument($driver, '/home/sargis/Загрузки/test/ISK____t9l_1-5.pdf', 3);
            sleep(2);

            $this->fillApplicantsData($driver, [
                'name' => 'ООО Правеж',
                'inn' => '888345345345',
                'legal_zipcode' => '654000',
                'legal_address' => 'пр. Советской Армии 33',
                'location_zipcode' => '654000',
                'location_address' => 'ул. Тореза 10',
                'procedural_status' => 'Истец',
                'ogrn' => '3453645567674',
                'kpp' => '345646456',
                'email' => 'johndoe@mail.ru',
                'phone' => '+79998887766'
            ]);

            $participants = [
                [
                    "first_name" => "Иван",
                    "last_name" => "Иванов",
                    "snils" => "34345345345",
                    "inn" => "435345345345",
                    "registration_zipcode" => "654000",
                    "registration_address" => "г. Новокузнецк, пр. Советской Армии 45",
                    "resident_zipcode" => "654000",
                    "resident_address" => "г. Новокузнецк, пр. Советской Армии 45",
                ],
                [
                    "first_name" => "Петр",
                    "last_name" => "Петров",
                    "snils" => "34345345342",
                    "inn" => "435345345345",
                    "registration_zipcode" => "654111",
                    "registration_address" => "г. Новокузнецк, пр. Советской Армии 47",
                    "resident_zipcode" => "654000",
                    "resident_address" => "г. Новокузнецк, пр. Советской Армии 47",
                ]
            ];

            $this->fillParticipantsData($driver, $participants);

            sleep(3);

            $this->fillCourtData($driver, 'Кемеровская область', 'Заводской районный суд г. Новокузнецка');

            $this->fillEssenceDocument($driver, '/home/sargis/Загрузки/test/ISK____t9l_1-5.pdf', 5);


            //Поставить галочку "Квитанция об уплате государственной пошлины"
            $driver->findElement(
                WebDriverBy::cssSelector('#Tax_Free')
            )->click();

            $this->fillPaymentDocument($driver, '/home/sargis/Загрузки/test/ISK____t9l_1-5.pdf', 2);

            $this->fillAttachmentDocuments($driver, [
                [
                    'path' => '/home/sargis/Загрузки/test/ISK____t9l_1-5.pdf',
                    'page_count' => 2,
                    'description' => 'File number one'
                ],
                [
                    'path' => '/home/sargis/Загрузки/test/ISK____t9l_6-7.pdf',
                    'page_count' => 7,
                    'description' => 'File number two'
                ],
            ]);

            sleep(10);

            $driver->quit();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
            $driver->quit();
        }

    }

    private function fillApplicantsData(RemoteWebDriver $driver, $applicant)
    {
        $driver->findElement(
            WebDriverBy::xpath('//button[contains(text(), "Добавить заявителя")]')
        )->click();

        $driver->manage()->timeouts()->implicitlyWait(3);

        $driver->findElement(
            WebDriverBy::xpath('//button[contains(text(), "Юридическое лицо")]')
        )->click();


        $driver->findElement(WebDriverBy::cssSelector('#Company_Name'))->sendKeys($applicant['name']);
        sleep(1);

        $driver->findElement(WebDriverBy::cssSelector('#Company_INN'))->sendKeys($applicant['inn']);
        sleep(1);

        $proceduralStatus = new WebDriverSelect(
            $driver->findElement(
                WebDriverBy::xpath('//select[contains(@id, "ProceduralStatus")]')
            )
        );
        $proceduralStatus->selectByVisibleText($applicant['procedural_status']);
        sleep(3);

        $driver->findElement(WebDriverBy::cssSelector('#Company_OGRN'))->sendKeys($applicant['ogrn']);
        sleep(1);

        $driver->findElement(WebDriverBy::cssSelector('#Company_KPP'))->sendKeys($applicant['kpp']);
        sleep(1);

        $driver->findElement(WebDriverBy::cssSelector('#Address_Legal_Index'))->sendKeys($applicant['legal_zipcode']);
        sleep(1);

        $driver->findElement(WebDriverBy::cssSelector('#Address_Legal_Address'))->sendKeys($applicant['legal_address']);
        sleep(1);

        $driver->findElement(WebDriverBy::cssSelector('#Address_Physical_Index'))->sendKeys($applicant['location_zipcode']);
        sleep(1);

        $driver->findElement(WebDriverBy::cssSelector('#Address_Physical_Address'))->sendKeys($applicant['location_address']);
        sleep(2);

        $driver->findElement(
            WebDriverBy::xpath('//input[contains(@title, "Введите адрес электронной почты представляемого лица")]')
        )->sendKeys($applicant['email']);
        sleep(3);

        $driver->findElement(
            WebDriverBy::xpath('//input[contains(@title, "Введите адрес электронной почты представляемого лица")]')
        )->sendKeys($applicant['phone']);
        sleep(3);

        $driver->findElement(
            WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Сохранить")]')
        )->click();
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

        $currentCourt->selectByVisibleText($courtJudiciary);

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

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Surname")]')
            )->sendKeys($participant['last_name']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Name")]')
            )->sendKeys($participant['last_name']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Permanent_Index")]')
            )->sendKeys($participant['registration_zipcode']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Permanent_Address")]')
            )->sendKeys($participant['registration_address']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Actual_Index")]')
            )->sendKeys($participant['resident_zipcode']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Address_Actual_Address")]')
            )->sendKeys($participant['resident_address']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "Snils")]')
            )->sendKeys($participant['snils']);

            sleep(1);

            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-body")]/div/div/form/div/div/div/div/input[contains(@id, "INN")]')
            )->sendKeys($participant['inn']);

            sleep(1);


            $driver->findElement(
                WebDriverBy::xpath('//div[contains(@class, "modal-footer")]/button[contains(text(), "Сохранить")]')
            )->click();

            sleep(3);
        }
    }

    private function fillAttachmentDocuments(RemoteWebDriver $driver, $files)
    {
        foreach ($files as $file) {
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
                $attachmentFileInput->sendKeys($file['path']);

                // Подождать 5 секунд для прогрузки файла
                sleep(5);

                // Указать количество страниц
                $driver->findElement(
                    WebDriverBy::xpath('//input[contains(@name, "pageNum")]')
                )->sendKeys($file['page_count']);

                $driver->findElement(
                    WebDriverBy::xpath('//input[contains(@name, "comment")]')
                )->sendKeys($file['description']);

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

    private function findElementSafe(RemoteWebDriver $driver, WebDriverBy $by)
    {
        try {
            return $driver->findElement($by);
        } catch (NoSuchElementException $exception) {
            return null;
        }
    }
}
