<?php
    namespace App\Helpers;

    class Pagination
    {
        public int $start;
        public int $totalCount;
        public int $totalPages;
        public int $onPage;
        private int $currentPage;

        public function __construct(array $params)
        {
            $this->start = 0;
            $this->totalPages = 0;

            $this->totalCount = $params['totalCount'] ?? 0;
            $this->currentPage = $params['currentPage'] ?? 1;
            $this->onPage = $params['onPage'] ?? 50;

            $this->getPages();
        }

        private function getPages():void
        {
            $this->totalPages = ceil($this->totalCount / $this->onPage);
            $this->start = ($this->currentPage - 1) * $this->onPage;
        }
    }
?>