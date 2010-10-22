<?php
if (!defined('NEWTYPE') && !defined('DWORKS')) { exit(1); }

class SupAction extends Action
{
    function handle($args)
    {
        parent::handle($args);

        $seconds = $this->trimmed('seconds');

        if (!$seconds) {
            $seconds = 15;
        }

        $updates = $this->getUpdates($seconds);

        header('Content-Type: application/json; charset=utf-8');

        print json_encode(array('updated_time' => date('c'),
                                'since_time' => date('c', time() - $seconds),
                                'available_periods' => $this->availablePeriods(),
                                'period' => $seconds,
                                'updates' => $updates));
    }

    function availablePeriods()
    {
        static $periods = array(86400, 43200, 21600, 7200,
                                3600, 1800, 600, 300, 120,
                                60, 30, 15);
        $available = array();
        foreach ($periods as $period) {
            $available[$period] = common_local_url('sup',
                                                   array('seconds' => $period));
        }

        return $available;
    }

    function getUpdates($seconds)
    {
        $notice = new Notice();

        # XXX: cache this. Depends on how big this protocol becomes;
        # Re-doing this query every 15 seconds isn't the end of the world.

        $divider = common_sql_date(time() - $seconds);

        $notice->query('SELECT profile_id, max(id) AS max_id ' .
                       'FROM ( ' .
                       'SELECT profile_id, id FROM notice ' .
                        ((common_config('db','type') == 'pgsql') ?
                       'WHERE extract(epoch from created) > (extract(epoch from now()) - ' . $seconds . ') ' :
                       'WHERE created > "'.$divider.'" ' ) .
                       ') AS latest ' .
                       'GROUP BY profile_id');

        $updates = array();

        while ($notice->fetch()) {
            $updates[] = array($notice->profile_id, $notice->max_id);
        }

        return $updates;
    }

    function isReadOnly($args)
    {
        return true;
    }
}
