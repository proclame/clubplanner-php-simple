<?php

namespace Proclame;

use GuzzleHttp\Client;

class ClubPlanner
{

    private Client $httpClient;

    public function __construct(protected string $api_url, protected string $api_token)
    {
        $this->httpClient = new Client();

    }

    /** Member Functions START */
    public function getMember($member_id)
    {
        return $this->request('member/getmember', 'id=' . $member_id);
    }

    public function getMembersByEmail($email): array
    {
        return $this->getAllMembers([1, 2, 3, 4, 5], ' and email_address = \'' . $email . '\'');
    }

    public function getMemberByEmail($email) // finds first member with this email
    {
        return $this->request('member/getmember', 'email=' . $email);
    }

    public function getSuspendedMembers()
    {
        return $this->request('member/getmembers', '', 'retention_status_id=-1');
    }

    public function getAllMembers(array|int $owner_id = 0, $extra_arguments = ''): array
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
        var_dump($this->request('member/ForgotPassword', 'memberid=' . $memberId));
    }

    public function addMember($data)
    {
        return $this->request('member/AddMember', http_build_query($data));
    }

    public function addSubscription($member_id, $sub_id, $startdate, $amount, $paymethod = '', $reference = '', $note = '')
    {
        return $this->request('member/AddSubscription', 'memberid=' . $member_id . '&subid=' . $sub_id . '&startdate=' . $startdate . '&amount=' . $amount . '&paymethod=' . $paymethod . '&reference=' . $reference . '&note=' . $note);
    }

    public function paySubscription($member_subscription_id, $amount, $paymethod = '', $reference = '')
    {
        return $this->request('member/PaySubscription', 'id=' . $member_subscription_id . '&amount=' . $amount . '&paymethod=' . $paymethod . '&reference=' . $reference);
    }

    public function updateMember($member_id, $updateData) // TEST
    {
        return $this->request('member/updatemember', 'memberid=' . $member_id . '&' . http_build_query($updateData));
    }

    // Subscribe to newsletter
    public function subscribeMember($member_id): void
    {
        $this->request('member/updatemember', 'memberid=' . $member_id . '&newsletter=1');
    }

    // Unsusbscribe from newsletter
    public function unsubscribeMember($member_id): void
    {
        $this->request('member/updatemember', 'memberid=' . $member_id . '&newsletter=0');
    }

    /** Member Functions END */

    /** General Functions START */
    public function getClubs(): array
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
        var_dump($this->request('member/GetOptions'));
    }

    // Get Statusses for club, Only used for "student"
    public function getStatusses(): void
    {
        var_dump($this->request('member/GetStatusses'));
    }

    public function updateStatus($memberId, $statusId): void
    {
        $this->request('member/UpdateStatus', 'memberid=' . $memberId . '&statusid=' . $statusId . '&from=API');
    }

    // Empty result, not used by OmniMove?
    public function getSubscriptionOptions($subscriptionId): void
    {
        var_dump($this->request('member/GetSubscriptionOptions', 'ownerid=1&subid=' . $subscriptionId));
    }

    /** General Functions END */
    /** Subscriptions Functions START */

    // Get different subscriptions (abonnementen)
    public function getSubscriptions(): array
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
        var_dump($this->request('member/GetCountvisits', 'fromdate=31-07-2018 00:00&todate=31-07-2018 23:59&owner=1'));

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
        var_dump($this->request('planner/GetSubscriptionGroups'));
    }

    public function getCalendarItem($calendarItemId)
    {
        return $this->request('planner/GetCalendarItem', 'id=' . $calendarItemId);
    }

    public function getCalendarItems($date, $calendarId, $days = 1, $employeeId = 0)
    {
        return $this->request('planner/GetCalendarItems', 'id=' . $calendarId . '&date=' . $date . '&days=' . $days . '&employeeid=' . $employeeId);
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

    public function updateSubscription(): void
    {
        $this->request('member/UpdateSubscription', 'id=92003&from=API&amount=50');
    }

    public function updateSubscriptionAmount(int $subscriptionId, float $amount): void
    {
        $this->request('member/UpdateSubscription', "id={$subscriptionId}&from=API&amount={$amount}");
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
        $userField = str_contains($memberId, '@') ? 'email' : 'memberid';

        return $this->request('member/VerifyPassword', $userField . '=' . $memberId . '&password=' . $password . '&from=API');
    }


    private function postRequest($action, $postParameters = [])
    {
        $url = 'https://' . $this->api_url . "/api/{$action}";
        $postParameters['token'] = $this->api_token;

        return $this->httpClient->request('POST', $url, ['form_params' => $postParameters])->getBody();
    }

    private function request($action, $parameters = '', $filter = '')
    {
            $url = 'https://' . $this->api_url . "/api/{$action}?token=" . $this->api_token;

            if ($parameters !== '') {
                $url .= "&{$parameters}";
            }

            if ($filter !== '') {
                $filter = urlencode($filter);
                $url .= "&filter={$filter}";
            }

            $res = $this->httpClient->request('GET', $url);

            return json_decode(utf8_encode($res->getBody()));
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
