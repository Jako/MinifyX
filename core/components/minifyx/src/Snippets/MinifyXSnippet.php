<?php
/**
 * MinifyX Snippet
 *
 * @package agenda
 * @subpackage snippet
 */

namespace TreehillStudio\Agenda\Snippets;

use AgendaCategories;
use AgendaEvents;
use AgendaLocations;
use xPDO;

/**
 * Class MinifyXSnippet
 */
class MinifyXSnippet extends Snippet
{
    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'start::normalizeDate::today 0:00' => '',
            'end::normalizeDate::today +1 month 0:00' => '',
            'sortby' => 'all_startdate',
            'sortdir' => 'ASC',
            'tpl' => 'tplAgendaEventRow',
            'wrapperTpl' => 'tplAgendaEventWrapper',
            'categoryTpl' => 'tplAgendaEventCategory',
            'imageTpl' => 'tplAgendaEventImage',
            'videoTpl' => 'tplAgendaEventVideo',
            'locationTpl' => 'tplAgendaEventLocation',
            'resourceTpl' => '',
            'emptyTpl' => 'tplAgendaEventEmpty',
            'locale' => $this->agenda->getOption('locale', null, 'en'),
            'daterangeSeparator' => $this->modx->lexicon('agenda.php_format_separator'),
            'daterangeFormat' => $this->modx->lexicon('agenda.php_format_daterange'),
            'durationParts::int' => 1,
            'calendars::explodeSeparated' => '',
            'categories::explodeSeparated' => '',
            'users' => '',
            'usergroups' => '',
            'contexts' => '',
            'locations::explodeSeparated' => '',
            'allowedRequestKeys::explodeSeparated' => '',
            'searchLocation::bool' => true,
            'searchCategory::bool' => true,
            'excludeEvents::explodeSeparated' => '',
            'excludeRepeats::explodeSeparated' => '',
            'listId::int' => $this->agenda->getOption('list_id', [], (isset($this->modx->resource)) ? $this->modx->resource->get('id') : 0),
            'detailId::int' => $this->agenda->getOption('detail_id', [], $this->modx->getOption('site_start')),
            'toPlaceholder' => '',
            'outputSeparator' => "\n",
            'limit::int' => 20,
            'offset::int' => 0,
            'totalVar' => 'agendalist.total'
        ];
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute()
    {
        // Request parameters
        $requestCalendar = $this->modx->getOption('calendar', $_REQUEST, '');
        if ($requestCalendar && (empty($this->getProperty('allowedRequestKeys')) || in_array('calendar', $this->getProperty('allowedRequestKeys')))) {
            $requestCalendarObject = $this->modx->getObject('AgendaCalendars', ['alias' => $requestCalendar]);
            if ($requestCalendarObject) {
                $this->properties['calendars'] = ($this->getProperty('calendars')) ? array_intersect($this->getProperty('calendars'), [$requestCalendarObject->get('alias')]) : [$requestCalendarObject->get('alias')];
            }
        }
        $requestCategory = $this->modx->getOption('category', $_REQUEST, '');
        if ($requestCategory && (empty($this->getProperty('allowedRequestKeys')) || in_array('category', $this->getProperty('allowedRequestKeys')))) {
            /** @var AgendaCategories $requestCategoryObject */
            $requestCategoryObject = $this->modx->getObject('AgendaCategories', ['alias' => $requestCategory]);
            if ($requestCategoryObject) {
                $this->properties['categories'] = ($this->getProperty('categories')) ? array_intersect($this->getProperty('categories'), [$requestCategoryObject->get('alias')]) : [$requestCategoryObject->get('alias')];
            }
        }
        $requestLocation = $this->modx->getOption('location', $_REQUEST, '');
        if ($requestLocation && (empty($this->getProperty('allowedRequestKeys')) || in_array('location', $this->getProperty('allowedRequestKeys')))) {
            /** @var AgendaLocations $requestLocationObject */
            $requestLocationObject = $this->modx->getObject('AgendaLocations', ['alias' => $requestLocation]);
            if ($requestLocationObject) {
                $this->properties['locations'] = ($this->getProperty('locations')) ? array_intersect($this->getProperty('locations'), [$requestLocationObject->get('alias')]) : [$requestLocationObject->get('alias')];
            }
        }
        $requestSearch = $this->modx->getOption('search', $_REQUEST, '');
        $search = ($requestSearch && (empty($this->getProperty('allowedRequestKeys')) || in_array('search', $this->getProperty('allowedRequestKeys')))) ? $requestSearch : '';

        $output = '';

        if (!$this->modx->loadClass('agenda.AgendaEvents', $this->agenda->getOption('modelPath'))) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load AgendaEvents class!', '', 'AgendaList');
            return $output;
        }
        $eventClass = new AgendaEvents($this->modx);
        $eventsOptions = [
            'startdate' => $this->getProperty('start'),
            'enddate' => $this->getProperty('end'),
            'sortby' => $this->getProperty('sortby'),
            'sortdir' => $this->getProperty('sortdir'),
            'limit' => (int)$this->getProperty('limit'),
            'offset' => (int)$this->getProperty('offset'),
            'calendars' => $this->getProperty('calendars'),
            'categories' => $this->getProperty('categories'),
            'users' => $this->getProperty('users'),
            'usergroups' => $this->getProperty('usergroups'),
            'contexts' => $this->getProperty('contexts'),
            'locations' => $this->getProperty('locations'),
            'excludeEvents' => $this->getProperty('excludeEvents'),
            'excludeRepeats' => $this->getProperty('excludeRepeats'),
            'search' => $search,
            'searchLocation' => $this->getProperty('searchLocation'),
            'searchCategory' => $this->getProperty('searchCategory'),

        ];
        $count = $eventClass->countEvents($eventsOptions);
        $events = $eventClass->getEvents($eventsOptions);

        $eventList = [];
        $idx = 1;
        foreach ($events as $event) {
            $eventArray = $event->toExtendedArray(array_merge($this->getProperties(), [
                'categoryTpl' => $this->getProperty('categoryTpl'),
                'imageTpl' => $this->getProperty('imageTpl'),
                'videoTpl' => $this->getProperty('videoTpl'),
                'locationTpl' => $this->getProperty('locationTpl'),
                'locale' => $this->getProperty('locale'),
                'daterangeSeparator' => $this->getProperty('daterangeSeparator'),
                'daterangeFormat' => $this->getProperty('daterangeFormat'),
                'durationParts' => (int)$this->getProperty('durationParts'),
                'detailId' => $this->getProperty('detailId'),
                'listId' => $this->getProperty('listId'),
                'idx' => $idx,
            ]));
            $eventList[] = $this->agenda->getChunk($this->getProperty('tpl'), array_merge($this->getProperties(), $eventArray));
            $idx++;
        }
        if (count($eventList)) {
            $output = $this->agenda->getChunk($this->getProperty('wrapperTpl'), array_merge($this->getProperties(), [
                'output' => implode($this->getProperty('outputSeparator'), $eventList),
                'count' => $count
            ]));
        } else {
            $output = $this->agenda->getChunk($this->getProperty('emptyTpl'), $this->getProperties());
        }

        if ($this->getProperty('toPlaceholder')) {
            $this->modx->setPlaceholder($this->getProperty('toPlaceholder'), $output);
            $output = '';
        }
        $this->modx->setPlaceholder($this->getProperty('totalVar'), $count);

        return $output;
    }
}
