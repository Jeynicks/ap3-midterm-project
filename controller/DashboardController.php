<?php
class DashboardController {
    public function __construct(private DashboardModel $dash) {}

    public function summary(): array {
        return [
            'totals'    => $this->dash->totals(),
            'statuses'  => $this->dash->plantingsByStatus(),
            'plantings' => $this->dash->recentPlantings(),
            'harvests'  => $this->dash->recentHarvests(),
        ];
    }
}