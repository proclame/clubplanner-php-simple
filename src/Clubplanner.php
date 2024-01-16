<?php

namespace App\Lib;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClubPlanner
{
    protected $cacheResults = true;

    /** Member Functions START */
    public function getMember($member_id)
    {
        $member = $this->request('member/getmember', 'id=' . $member_id);

        return property_exists($member, 'Id') ? $member : null;
    }

    public function getMemberByEmail($email)
    {
        return $this->request('member/getmember', 'email=' . $email);
    }

    public function getMembersByEmail($email)
    {
        return $this->request('member/getmembers', '', 'Email_Address=\'' . $email . '\'');
    }

    public function getSuspendedMembers()
    {
        return $this->request('member/getmembers', '', 'retention_status_id=-1');
    }

    public function getAllMembers($owner_id = 0, $extra_arguments = '')
    {
        $stop = false;
        $allMembers = [];
        $startFrom = 0;

        if (is_array($owner_id)) {
            $ownerquery = '( owner = \'-1\'';

            foreach ($owner_id as $a) {
                $ownerquery .= " or owner = '{$a}'";
            }

            $ownerquery .= ')';
        } else {
            $ownerquery = "owner = '{$owner_id}'";
        }

        while ($stop === false) {
            //$members = $this->request('member/getmembers', '', 'owner != 2 and member_id > ' . $startFrom);
            $members = $this->request('member/getmembers', '', "{$ownerquery} and member_id > " . $startFrom . ' ' . $extra_arguments); // Club 1 & 3 = OmniMove

            if (count($members) < 1000) {
                $stop = true;
            } else {
                $startFrom = end($members)->Id;
            }
            $allMembers = array_merge($allMembers, $members);
        }
        $newAllMembers = [];

        foreach ($allMembers as $member) {
            $newAllMembers[$member->Id] = $member;
        }

        //echo count($allMembers);
        return $newAllMembers;
        /*
         * Filters:
         *  - created_on > '2018/05/1'
         *  - newsletter=1
         *  - member_id>0 and member_id<10
         *  - last_visit > '2018/05/28'
         */
    }

    public function forgotPassword($memberId): void
    {
        dd($this->request('member/ForgotPassword', 'memberid=' . $memberId));
    }

    public function forgotPasswordByEmail(string $email): void
    {
        dd($this->request('member/ForgotPassword', 'email=' . $email));
    }

    public function addMember($data)
    {
        return $this->request('member/AddMember', http_build_query($data));
    }

    /*
        public function addMember() // TEST
        {
            return $this->request('member/AddMember', 'firstname=Nick&lastname=TEST');
        }

        public function updateMemberInfo($member_id) // TEST
        {
            return $this->request('member/updatemember', 'memberid=' . $member_id . '&info10=TEST');
        }
    */
    // Subscribe to newsletter
    public function subscribeMember($member_id): void
    {
        $member = $this->request('member/updatemember', 'memberid=' . $member_id . '&newsletter=1');
    }

    // Unsusbscribe from newsletter
    public function unsubscribeMember($member_id): void
    {
        $member = $this->request('member/updatemember', 'memberid=' . $member_id . '&newsletter=0');
    }

    /** Member Functions END */

    /** General Functions START */
    public function getClubs()
    {
        $clubs = [];

        foreach ($this->request('general/GetClubs') as $club) {
            $clubs[$club->Id] = $club;
        }

        return $clubs;
    }

    // Get Options for club, not used in OmniMove
    public function getOptions(): void
    {
        dd($this->request('member/GetOptions'));
    }

    // Get Statusses for club, Only used for "student"
    public function getStatusses(): void
    {
        dd($this->request('member/GetStatusses'));
    }

    public function updateStatus($memberId, $statusId): void
    {
        $this->request('member/UpdateStatus', 'memberid=' . $memberId . '&statusid=' . $statusId . '&from=API');
    }

    // Empty result, not used by OmniMove?
    public function getSubscriptionOptions($subscriptionId): void
    {
        dd($this->request('member/GetSubscriptionOptions', 'ownerid=1&subid=' . $subscriptionId));
    }

    /** General Functions END */
    /** Subscriptions Functions START */

    // Get different subscriptions (abonnementen)
    public function getSubscriptions()
    {
        $subscriptions = [];

        foreach ($this->request('member/getsubscriptions') as $subscription) {
            $subscriptions[$subscription->SubscriptionId] = $subscription;
        }

        return $subscriptions;
    }

    public function getMemberSubscriptions($memberId)
    {
        return $this->request('member/GetMemberSubscriptions', 'memberid=' . $memberId);
    }

    public function getMemberSubscriptions2($subscriptionId = '', $fromdate = '', $todate = '', $owner = '')
    {
        $fromdate = $fromdate === '' ? date('d-m-Y') : $fromdate;
        $todate = $todate === '' ? date('d-m-Y') : $todate;

        $ownerQuery = $owner !== '' ? '&owner=' . $owner : '';

        $subscriptionIdQuery = $subscriptionId !== '' ? '&subscriptionid=' . $subscriptionId : '';

        /**
         * Startersmaand = 170
         * Prof Program = 184
         * Lifestyle program = 185
         * Proefles = 10
         * Comfort Start = 140.
         */
        $i = 0;

        return $this->request('member/GetMemberSubscriptions', 'fromdate=' . $fromdate . '&todate=' . $todate . $subscriptionIdQuery . $ownerQuery);
    }

    /** Subscriptions Functions END */
    /** Checkins / Visits Functions START */

    // Checkins
    public function getVisits($memberId, $fromdate = '01-01-2017', $todate = '')
    {
        $todate = $todate === '' ? date('d-m-Y') : $todate;

        return $this->request('member/getvisits', 'fromdate=' . $fromdate . '&todate=' . $todate . '&memberid=' . $memberId);
        /*
         * Parameters:
         *  - fromdate=31-07-2018
         *  - todate=01-08-2018
         *  - owner=1
         */
    }

    // Checkins
    public function getCountvisits(): void
    {
        dd($this->request('member/GetCountvisits', 'fromdate=31-07-2018 00:00&todate=31-07-2018 23:59&owner=1'));

        /*
         * Parameters:
         *  - fromdate=31-07-2018
         *  - todate=01-08-2018
         *  - owner=1
         */
    }

    /** Checkins / Visits Functions END */

    /** Planner Functions START */
    public function getCalendars()
    {
        return $this->request('planner/GetCalendars');
    }

    public function getSubscriptionGroups(): void
    {
        dd($this->request('planner/GetSubscriptionGroups'));
    }

    public function getCalendarItems($date, $calendarId, $days = 1, $employeeId = 0)
    {
        return $this->request('planner/GetCalendarItems', 'id=' . $calendarId . '&date=' . $date . '&days=' . $days . '&employeeid=' . $employeeId);
    }

    public function getCalendarItem($calendarItemId)
    {
        return $this->request('planner/GetCalendarItem', 'id=' . $calendarItemId);
    }

    public function updateCalendarItem($calendarItemId, $values)
    {
        $querystring = http_build_query(['id' => $calendarItemId] + $values);

        return $this->request('planner/UpdateCalendarItem', $querystring);
    }

    public function getCalendarItemReservations($calendarId)
    {
        return $this->request('planner/GetReservations', 'calitemid=' . $calendarId);
    }

    public function getMemberReservations($memberId)
    {
        return $this->request('planner/GetReservations', 'memberid=' . $memberId);
    }

    public function getCalendarReservations($calId, $fromdate = '', $todate = '')
    {
        $fromdate = $fromdate === '' ? date('d-m-Y') : $fromdate;
        $todate = $todate === '' ? date('d-m-Y') : $todate;
        $fromdate .= ' 00:00:00';
        $todate .= ' 23:59:00';

        return $this->request('planner/GetReservations', 'calid=' . $calId . '&fromdate=' . $fromdate . '&todate=' . $todate);
    }

    public function cancelMemberReservation($reservationId, $force = false)
    {
        $forceRequest = $force === true ? '&logtype=0' : '';

        return $this->request('planner/CancelReservation', 'from=API&reservationid=' . $reservationId . $forceRequest);
    }

    public function addMemberReservation($memberId, $itemId, $fullName = '', $force = false)
    {
        $forceRequest = $force === true ? '&logtype=0' : '';
        $fullnameQuery = $fullName !== '' ? '&fullname=' . $fullName : '';

        return $this->request('planner/AddReservation', 'from=API&memberid=' . $memberId . '&itemid=' . $itemId . $fullnameQuery . $forceRequest);
        ///  <param name="memberid"></param>
        /// <param name="itemid"></param>
        /// <param name="quantity">optional</param>
        /// <param name="logtype">optional: Enum LogType</param>
        /// <param name="fullname">optional name</param>
        /// <param name="from">optional</param>
    }

    public function getEmployees()
    {
        return $this->request('employee/GetEmployees');
    }

    public function getEmployee($employeeId)
    {
        return $this->request('employee/GetEmployee', 'id=' . $employeeId);
    }

    public function updateSubscription(int $subscriptionId, array $parameters = [])
    {
        $parameterQueryString = http_build_query($parameters);

        return $this->request('member/UpdateSubscription', "id={$subscriptionId}&from=API&{$parameterQueryString}");
    }

    public function updateSubscriptionAmount(int $subscriptionId, float $amount)
    {
        return $this->request('member/UpdateSubscription', "id={$subscriptionId}&from=API&amount={$amount}");
    }

    public function deleteSubscription($subscriptionId): void
    {
        $this->request('member/DeleteSubscription', 'id=' . $subscriptionId . '&from=API');
    }

    public function sendSMS($memberId, $message): void
    {
        $message = str_replace(["\n", "\r"], '  ', strip_tags($message));
        $message = urlencode($message);
        $this->request('member/SendSMS', 'memberid=' . $memberId . '&message=' . $message . '&from=API');
    }

    public function sendEmail($memberId, $message, $subject = 'Nieuw bericht betreffende uw reservatie', $fromName = 'OmniMove Reservaties'): void
    {
        // $message = str_replace(["\n", "\r"], '  ', strip_tags($message));
        $post = [];
        $post['memberId'] = $memberId;
        $post['message'] = $message;
        $post['subject'] = $subject;
        $post['from'] = $fromName;
        $post['address'] = '';
        $this->postRequest('member/SendEmail', $post);
    }

    public function verifyPassword($memberId, $password)
    {
        return $this->request('member/VerifyPassword', 'email=' . $memberId . '&password=' . $password . '&from=API');
    }

    public function noCache(): static
    {
        $this->cacheResults = false;

        return $this;
    }

    public function useCache(): static
    {
        $this->cacheResults = true;

        return $this;
    }

    private function postRequest($action, $postParameters = []): void
    {
        $url = 'https://' . config('services.clubplanner.url') . "/api/{$action}";
        $postParameters['token'] = config('services.clubplanner.token');

        $res = Http::post($url, $postParameters);
    }

    private function request($action, $parameters = '', $filter = '')
    {
        $cacheKey = md5($action . $parameters . $filter);

        if ($this->cacheResults === false) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () use ($action, $parameters, $filter) {
            $url = 'https://' . config('services.clubplanner.url') . "/api/{$action}?token=" . config('services.clubplanner.token');

            if ($parameters !== '') {
                $url .= "&{$parameters}";
            }

            if ($filter !== '') {
                $filter = urlencode($filter);
                $url .= "&filter={$filter}";
            }
            // $res = $this->guzzle->request('GET', $url);
            $res = Http::get($url);

            return json_decode($res->body());
        });
    }

    public function getPosSaleItems(int $gymID, string $date, int $days)
    {
        return $this->request('pos/GetPosSaleItems', 'date=' . $date . '&ownerid=' . $gymID . '&days=' . $days . '&from=API');
    }

    public function addPosItem(int $gymID, $totalAmount, $itemName, $revenueGroupId, $payMethodId, $vat, $memberId, $quantity = 1, $createdOn = 'today', $posPointId = 1)
    {
        return $this->request('pos/AddPosItem', http_build_query([
            'ownerid' => $gymID,
            'totalamount' => $totalAmount,
            'itemname' => $itemName,
            'revenuegroupid' => $revenueGroupId,
            'paymethodid' => $payMethodId,
            'vat' => $vat,
            'memberid' => $memberId,
            'quantity' => $quantity,
        ]));
    }
}
