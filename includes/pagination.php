<?php
class Pagination {
    public $numItems;
    public $numItemsPerPage;
    public $currentPage;

    public function __construct($numItems = 0, $numItemsPerPage = 1, $currentPage = 1) {
        $this->numItems = $numItems;
        $this->numItemsPerPage = $numItemsPerPage;
        $this->currentPage = $currentPage;
    }

    /**
     * Returns whether there is a page after the current page
     */
    public function hasNextPage() {
        return ($this->currentPage === $this->getNumPages()) ? false : true;
    }

    /**
     * Returns whether there is a page before the current page
     */
    public function hasPrevPage() {
        return ($this->currentPage === 1) ? false : true;
    }

    /**
     * Returns the total number of pages
     */
    public function getNumPages() {
        return ceil($this->numItems / $this->numItemsPerPage);
    }
}

?>

