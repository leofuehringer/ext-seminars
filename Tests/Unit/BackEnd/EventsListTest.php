<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Backend\Template\DocumentTemplate;

/**
 * Test case.
 *
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Niels Pardon <mail@niels-pardon.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class Tx_Seminars_Tests_Unit_BackEnd_EventsListTest extends Tx_Phpunit_TestCase
{
    /**
     * @var Tx_Seminars_BackEnd_EventsList
     */
    protected $fixture = null;
    /**
     * @var Tx_Oelib_TestingFramework
     */
    protected $testingFramework = null;

    /**
     * @var int PID of a dummy system folder
     */
    protected $dummySysFolderPid = 0;

    /**
     * @var Tx_Seminars_BackEnd_Module a dummy BE module
     */
    protected $backEndModule = null;

    /**
     * @var string the original language of the back-end module
     */
    protected $originalLanguage = '';

    protected function setUp()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('seminars')->setAsBoolean('enableConfigCheck', false);

        // Sets the localization to the default language so that all tests can
        // run even if the BE user has its interface set to another language.
        $this->originalLanguage = $GLOBALS['LANG']->lang;
        $GLOBALS['LANG']->lang = 'default';

        // Loads the locallang file for properly working localization in the tests.
        $GLOBALS['LANG']->includeLLFile('EXT:seminars/Resources/Private/Language/BackEnd/locallang.xlf');

        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_seminars');

        $this->dummySysFolderPid = $this->testingFramework->createSystemFolder();

        $this->backEndModule = new Tx_Seminars_BackEnd_Module();
        $this->backEndModule->id = $this->dummySysFolderPid;
        $this->backEndModule->setPageData([
            'uid' => $this->dummySysFolderPid,
            'doktype' => Tx_Seminars_BackEnd_AbstractList::SYSFOLDER_TYPE,
        ]);

        $document = new DocumentTemplate();
        $this->backEndModule->doc = $document;
        $document->backPath = $GLOBALS['BACK_PATH'];
        $document->docType = 'xhtml_strict';

        $this->fixture = new Tx_Seminars_BackEnd_EventsList($this->backEndModule);

        $backEndGroup = Tx_Oelib_MapperRegistry::get(
            Tx_Seminars_Mapper_BackEndUserGroup::class)->getLoadedTestingModel(
            ['tx_seminars_events_folder' => $this->dummySysFolderPid + 1]
        );
        $backEndUser = Tx_Oelib_MapperRegistry::get(
            Tx_Seminars_Mapper_BackEndUser::class)->getLoadedTestingModel(
            ['usergroup' => $backEndGroup->getUid()]
        );
        Tx_Oelib_BackEndLoginManager::getInstance()->setLoggedInUser($backEndUser);
    }

    protected function tearDown()
    {
        // Resets the language of the interface to the value it had before
        // we set it to "default" for testing.
        $GLOBALS['LANG']->lang = $this->originalLanguage;

        $this->testingFramework->cleanUp();
    }

    /////////////////////////////////////////
    // Tests for the events list functions.
    /////////////////////////////////////////

    public function testShowContainsNoBodyHeaderWithEmptySystemFolder()
    {
        self::assertNotContains(
            '<td class="datecol">',
            $this->fixture->show()
        );
    }

    public function testShowContainsTableBodyHeaderForOneEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            ['pid' => $this->dummySysFolderPid]
        );

        self::assertContains(
            '<td class="datecol">',
            $this->fixture->show()
        );
    }

    public function testShowContainsNoBodyHeaderIfEventIsOnOtherPage()
    {
        // Puts this record on a non-existing page. This is intentional.
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            ['pid' => $this->dummySysFolderPid + 1]
        );

        self::assertNotContains(
            '<td class="datecol">',
            $this->fixture->show()
        );
    }

    public function testShowContainsEventTitleForOneEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
            ]
        );

        self::assertContains(
            'event_1',
            $this->fixture->show()
        );
    }

    public function testShowContainsEventTitleForTwoEvents()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
            ]
        );
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_2',
            ]
        );

        self::assertContains(
            'event_1',
            $this->fixture->show()
        );
        self::assertContains(
            'event_2',
            $this->fixture->show()
        );
    }

    public function testShowContainsEventTitleForOneHiddenEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
                'hidden' => 1,
            ]
        );

        self::assertContains(
            'event_1',
            $this->fixture->show()
        );
    }

    public function testShowContainsEventTitleForOneTimedEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
                'endtime' => $GLOBALS['SIM_EXEC_TIME'] - 1000,
            ]
        );

        self::assertContains(
            'event_1',
            $this->fixture->show()
        );
    }

    public function testShowForOneEventContainsAccreditationNumber()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
                'accreditation_number' => 'accreditation number 123',
            ]
        );

        self::assertContains(
            'accreditation number 123',
            $this->fixture->show()
        );
    }

    public function testShowForOneEventContainsHtmlSpecialCharedAccreditationNumber()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
                'accreditation_number' => '&"<>',
            ]
        );

        self::assertContains(
            '&amp;&quot;&lt;&gt;',
            $this->fixture->show()
        );
    }

    public function testShowContainsCanceledStatusIconForCanceledEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_CANCELED,
            ]
        );

        self::assertContains(
            '<img src="/typo3conf/ext/seminars/Resources/Public/Icons/Canceled.png" title="canceled" alt="canceled"/>',
            $this->fixture->show()
        );
    }

    public function testShowContainsConfirmedStatusIconForConfirmedEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_CONFIRMED,
            ]
        );

        self::assertContains(
            '<img src="/typo3conf/ext/seminars/Resources/Public/Icons/Confirmed.png" title="confirmed" alt="confirmed"/>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainCanceledOrConfirmedStatusIconForPlannedEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_PLANNED,
            ]
        );

        self::assertNotContains(
            '<img src="/typo3conf/ext/seminars/Resources/Public/Icons/Canceled.png" title="canceled" alt="canceled"/>',
            $this->fixture->show()
        );

        self::assertNotContains(
            '<img src="/typo3conf/ext/seminars/Resources/Public/Icons/Confirmed.png" title="confirmed" alt="confirmed"/>',
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showForEventWithRegistrationsContainsEmailButton()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'registrations' => 1,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            [
                'pid' => $this->dummySysFolderPid,
                'seminar' => $eventUid,
            ]
        );

        self::assertContains(
            '<button><p>E-mail</p></button>',
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showForEventWithoutRegistrationsNotContainsEmailButton()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'registrations' => 0,
            ]
        );

        self::assertNotContains(
            '<button><p>E-mail</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainConfirmButtonForEventThatIsAlreadyConfirmed()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_CONFIRMED,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertNotContains(
            '<button><p>Confirm</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainConfirmButtonForPlannedEventThatHasAlreadyBegun()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] - 42,
            ]
        );

        self::assertNotContains(
            '<button><p>Confirm</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowContainsConfirmButtonForPlannedEventThatHasNotStartedYet()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_PLANNED,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertContains(
            '<button><p>Confirm</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowContainsConfirmButtonForCanceledEventThatHasNotStartedYet()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_CANCELED,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertContains(
            '<button><p>Confirm</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainConfirmButtonForTopicRecords()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'object_type' => Tx_Seminars_Model_Event::TYPE_TOPIC,
            ]
        );

        self::assertNotContains(
            '<button><p>Confirm</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowContainsConfirmButtonWithVariableEventUidInHiddenField()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertContains(
            '<button><p>Confirm</p></button>' .
            '<input type="hidden" name="eventUid" value="' . $uid . '" />',
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showDoesNotContainConfirmButtonForHiddenEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'hidden' => 1,
            ]
        );

        self::assertNotContains(
            '<button><p>Confirm</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainCancelButtonForAlreadyCanceledEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_CANCELED,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertNotContains(
            '<button><p>Cancel</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainCancelButtonPlannedEventThatHasAlreadyBegun()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] - 42,
            ]
        );

        self::assertNotContains(
            '<button><p>Cancel</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowContainsCancelButtonForPlannedEventThatHasNotStartedYet()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertContains(
            '<button><p>Cancel</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowContainsCancelButtonForConfirmedEventThatHasNotStartedYet()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
                'cancelled' => Tx_Seminars_Model_Event::STATUS_CONFIRMED,
            ]
        );

        self::assertContains(
            '<button><p>Cancel</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowDoesNotContainCancelButtonForTopicRecords()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
                'object_type' => Tx_Seminars_Model_Event::TYPE_TOPIC,
            ]
        );

        self::assertNotContains(
            '<button><p>Cancel</p></button>',
            $this->fixture->show()
        );
    }

    public function testShowContainsCancelButtonWithVariableEventUidInHiddenField()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'begin_date' => $GLOBALS['SIM_EXEC_TIME'] + 42,
            ]
        );

        self::assertContains(
            '<button><p>Cancel</p></button>' .
            '<input type="hidden" name="eventUid" value="' . $uid . '" />',
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showDoesNotContainCancelButtonForHiddenEvent()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'hidden' => 1,
            ]
        );

        self::assertNotContains(
            '<button><p>Cancel</p></button>',
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showContainsCsvExportButtonForEventWithRegistration()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'needs_registration' => 1,
            ]
        );

        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            [
                'pid' => $this->dummySysFolderPid,
                'seminar' => $eventUid,
            ]
        );

        self::assertContains(
            '=' . $eventUid,
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showDoesNotContainCsvExportButtonForHiddenEventWithRegistration()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'hidden' => 1,
                'needs_registration' => 1,
            ]
        );

        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            [
                'pid' => $this->dummySysFolderPid,
                'seminar' => $eventUid,
            ]
        );

        self::assertNotContains(
            '<a href="mod.php?M=web_txseminarsM2&amp;csv=1&amp;id=' .
                $this->dummySysFolderPid .
                '&amp;tx_seminars_pi2[table]=tx_seminars_attendances' .
                '&amp;tx_seminars_pi2[eventUid]=' . $eventUid . '">',
            $this->fixture->show()
        );
    }

    /**
     * @test
     */
    public function showContainsEventFromSubfolder()
    {
        $subfolderPid = $this->testingFramework->createSystemFolder(
            $this->dummySysFolderPid
        );
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'title' => 'Event in subfolder',
                'pid' => $subfolderPid,
            ]
        );

        self::assertContains(
            'Event in subfolder',
            $this->fixture->show()
        );
    }

    public function testShowForEventWithRegistrationHasShowLink()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            ['pid' => $this->dummySysFolderPid, 'needs_registration' => 1]
        );

        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            ['pid' => $this->dummySysFolderPid, 'seminar' => $eventUid]
        );

        self::assertContains(
            $GLOBALS['LANG']->getLL('label_show_event_registrations'),
            $this->fixture->show()
        );
    }

    public function testShowForEventWithoutRegistrationDoesNotHaveShowLink()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            ['pid' => $this->dummySysFolderPid, 'needs_registration' => 1]
        );

        self::assertNotContains(
            $GLOBALS['LANG']->getLL('label_show_event_registrations'),
            $this->fixture->show()
        );
    }

    public function testShowLinkLinksToRegistrationsTab()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            ['pid' => $this->dummySysFolderPid, 'needs_registration' => 1]
        );

        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            ['pid' => $this->dummySysFolderPid, 'seminar' => $eventUid]
        );

        self::assertContains(
            '&amp;subModule=2',
            $this->fixture->show()
        );
    }

    public function testShowLinkLinksToTheEvent()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            ['pid' => $this->dummySysFolderPid, 'needs_registration' => 1]
        );

        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            ['pid' => $this->dummySysFolderPid, 'seminar' => $eventUid]
        );

        self::assertContains(
            '&amp;eventUid=' . $eventUid,
            $this->fixture->show()
        );
    }

    public function testShowForHiddenEventWithRegistrationDoesNotHaveShowLink()
    {
        $eventUid = $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'needs_registration' => 1,
                'hidden' => 1,
            ]
        );

        $this->testingFramework->createRecord(
            'tx_seminars_attendances',
            ['pid' => $this->dummySysFolderPid, 'seminar' => $eventUid]
        );

        self::assertNotContains(
            $GLOBALS['LANG']->getLL('label_show_event_registrations'),
            $this->fixture->show()
        );
    }

    /////////////////////////
    // Tests for the icons.
    /////////////////////////

    public function testHasEventIcon()
    {
        $this->testingFramework->createRecord(
            'tx_seminars_seminars',
            [
                'pid' => $this->dummySysFolderPid,
                'title' => 'event_1',
                'object_type' => Tx_Seminars_Model_Event::TYPE_COMPLETE,
            ]
        );

        self::assertContains(
            'EventComplete.gif',
            $this->fixture->show()
        );
    }

    ////////////////////////////////
    // Tests for the localization.
    ////////////////////////////////

    public function testLocalizationReturnsLocalizedStringForExistingKey()
    {
        self::assertEquals(
            'Events',
            $GLOBALS['LANG']->getLL('title')
        );
    }

    ///////////////////////////////////////////
    // Tests concerning the new record button
    ///////////////////////////////////////////

    public function testEventListCanContainNewButton()
    {
        self::assertContains(
            'newRecordLink',
            $this->fixture->show()
        );
    }

    public function testNewButtonForNoEventStorageSettingInUserGroupsSetsCurrentPageIdAsNewRecordPid()
    {
        $backEndUser = Tx_Oelib_MapperRegistry::get(
            Tx_Seminars_Mapper_BackEndUser::class)->getLoadedTestingModel([]);
        Tx_Oelib_BackEndLoginManager::getInstance()->setLoggedInUser(
            $backEndUser
        );

        self::assertContains(
            'edit[tx_seminars_seminars][' . $this->dummySysFolderPid . ']=new',
            $this->fixture->show()
        );
    }

    public function testNewButtonForEventStoredOnCurrentPageHasCurrentFolderLabel()
    {
        $backEndUser = Tx_Oelib_MapperRegistry::get(
            Tx_Seminars_Mapper_BackEndUser::class)->getLoadedTestingModel([]);
        Tx_Oelib_BackEndLoginManager::getInstance()->setLoggedInUser(
            $backEndUser
        );

        self::assertContains(
            sprintf(
                $GLOBALS['LANG']->getLL('label_create_record_in_current_folder'),
                '',
                $this->dummySysFolderPid
            ),
            $this->fixture->show()
        );
    }

    public function testNewButtonForEventStorageSettingSetInUsersGroupSetsThisPidAsNewRecordPid()
    {
        $newEventFolder = Tx_Oelib_BackEndLoginManager::getInstance()->
            getLoggedInUser(Tx_Seminars_Mapper_BackEndUser::class)
                ->getEventFolderFromGroup();

        self::assertContains(
            'edit[tx_seminars_seminars][' . $newEventFolder . ']=new',
            $this->fixture->show()
        );
    }

    public function testNewButtonForEventStoredInPageDetermindedByGroupHasForeignFolderLabel()
    {
        $newEventFolder = Tx_Oelib_BackEndLoginManager::getInstance()->
            getLoggedInUser(Tx_Seminars_Mapper_BackEndUser::class)
                ->getEventFolderFromGroup();

        self::assertContains(
            sprintf(
                $GLOBALS['LANG']->getLL('label_create_record_in_foreign_folder'),
                '',
                $newEventFolder
            ),
            $this->fixture->show()
        );
    }
}
