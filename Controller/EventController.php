<?php
/**
 * Created by PhpStorm.
 * User: Tobias
 * Date: 2014-10-10
 * Time: 23:05
 */

namespace Controller;


use model\DbException;
use model\EmptyDescriptionException;
use model\EmptyTitleException;
use Model\Event;
use Model\EventModel;
use model\EventRepository;
use model\LoginModel;
use model\UserRepository;
use model\WrongDayFormatException;
use model\WrongMonthFormatException;
use model\WrongTimeFormatException;
use View\EventView;
use view\NavigationView;

class EventController {
    private $userRepository;
    private $loginModel;
    private $eventRepository;
    private $eventView;
    private $eventModel;

    public function __construct(){
        $this->userRepository = new UserRepository();
        $this->loginModel = new LoginModel();
        $this->eventRepository = new EventRepository();
        $this->eventView = new EventView();
        $this->eventModel = new EventModel();
    }

    public function renderEvent(){
        if ($this->loginModel->isUserLoggedIn() === true) {
            $this->getEvents();
            return $this->eventView->renderEvent();
        }
         NavigationView::redirectToLoginForm();
    }

    public function renderEventList(){
        if ($this->loginModel->isUserLoggedIn() === true) {
            $this->getEvents();
            return $this->eventView->renderEventList();
        }
        NavigationView::redirectToLoginForm();
    }

    public function renderAlterEventForm(){
        $this->getEvents();
        return $this->eventView->renderAlterEventForm();
    }

    /**
     * if user has pressed the add event link @return string the modal/popup
     * else @return bool false
     */
    public function hasUserPressedShowEventForm(){
        if ($this->eventView->hasUserPressedShowEventForm() && $this->loginModel->isUserLoggedIn() === true) {
            $this->getEvents();
            return $this->eventView->renderAddEventForm();
        }
        NavigationView::redirectToLoginForm();
        return false;
    }

    /**
     * checks if input from the add event form or update event form is valid
     */
    public function checkIfInputIsValid(){
        if ($this->loginModel->isUserLoggedIn() === true) {
            try {
                if ($this->eventModel->validateInput($this->eventView->getTitle(),
                        $this->eventView->getMonth(), $this->eventView->getCurrentMonth(), $this->eventView->getDay(),
                        $this->eventView->getCurrentDay(), $this->eventView->getStartHour(),
                        $this->eventView->getStartMinute(), $this->eventView->getEndHour(),
                        $this->eventView->getEndMinute(), $this->eventView->getDescription()) === true
                ) {
                    if ($this->eventView->hasUserPressedAddEvent()) {
                        $this->addEvent();
                    } else {
                        $this->alterEvent();
                    }
                    return;

                }
            } catch (EmptyTitleException $e) {
                $this->eventView->setMissingTitleMessage();

            } catch (EmptyDescriptionException $e) {
                $this->eventView->setMissingDescriptionMessage();

            } catch (WrongDayFormatException $e) {
                $this->eventView->setUnexpectedErrorMessage();

            } catch (WrongTimeFormatException $e) {
                $this->eventView->setWrongTimeFormatMessage();

            } catch (WrongMonthFormatException $e) {
                $this->eventView->setUnexpectedErrorMessage();

            }
            $this->eventModel->setMessage($this->eventView->getMessage());
            NavigationView::redirectToModal();
            return;
        }
        NavigationView::redirectToLoginForm();
    }

    public function alterEvent(){
        $event = new Event($this->eventView->getTitle(), $this->eventView->getMonth(),
            $this->eventView->getYear(), $this->eventView->getDay(), $this->eventView->getStartHour(),
            $this->eventView->getStartMinute(), $this->eventView->getEndHour(),
            $this->eventView->getEndMinute(), $this->eventView->getDescription(),
            $this->eventView->getEventId());
        try{
            $this->eventRepository->Update($event);
            NavigationView::redirectToCalendar();
        } catch(DbException $e){
            NavigationView::redirectToErrorPage();
        }

    }

    /**
     * tries to add en event
     * @throws DbException
     */
    private  function addEvent(){

        $event = new Event($this->eventView->getTitle(), $this->eventView->getMonth(), $this->eventView->getYear(),
                           $this->eventView->getDay(), $this->eventView->getStartHour(),
                           $this->eventView->getStartMinute(), $this->eventView->getEndHour(),
                           $this->eventView->getEndMinute(), $this->eventView->getDescription());

        try {
            $userId = $this->userRepository->getUserId($this->loginModel->getUserName());
            $this->eventRepository->add($event, $userId);
            NavigationView::redirectToCalendar();
        } catch (DbException $e) {
            NavigationView::redirectToErrorPage();

        }

    }

    public function deleteEvent(){
        try {
            $userId = $this->userRepository->getUserId($this->loginModel->getUserName());
            $title = $this->eventView->getEventTitle();
            $this->eventRepository->deleteEvent($title, $userId);
            NavigationView::redirectToCalendar();

        } catch (DbException $e) {
            NavigationView::redirectToErrorPage();
        }

    }

    public function getEvents(){
        try {
            $userId = $this->userRepository->getUserId($this->loginModel->getUserName());
            $events = $this->eventRepository->getEvents($userId);
            $this->eventView->setEvents($events);
        } catch (DbException $e) {
            NavigationView::redirectToErrorPage();
        }
    }


} 