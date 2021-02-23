<?php
//how it should work
/*
require_once("paging.class.php");

$query="SELECT * FROM table";
$p = new paging( $query );

$paging=$p->get_paging();
$content=sql_query($p->query);
*/

class paging
{
    public $num_pages;

    /**
     * @var string|mixed
     */
    public $paging_separator;

    /**
     * @var string|mixed
     */
    public $paging_format;

    /**
     * @var bool|mixed
     */
    public $paging_hide_prev_next;

    /**
     * @var string|mixed
     */
    public $paging_previous_text;

    /**
     * @var string|mixed
     */
    public $paging_next_text;

    /**
     * @var string|mixed
     */
    public $prefix;

    /**
     * @var string|mixed
     */
    public $hash_secret;

    /**
     * @var string|mixed
     */
    public $str_ext_argv;

    /**
     * @var null|mixed|float|int|string
     */
    public $int_num_result;

    /**
     * @var int|mixed
     */
    public $page;

    /**
     * @var mixed|string
     */
    public $query;

    /**
     * @var mixed
     */
    public $order;

    public $asc;

    /**
     * @var mixed
     */
    public $rows;

    /**
     * @var mixed|int
     */
    public $total;

    public function paging($query, $int_num_result = null, $order = null, $asc = true, $prefix = '', $num_pages = 10)
    {
        $this->num_pages = $num_pages;

        $this->paging_separator = '&nbsp;'; // e.g. 1 2 3 4 5
        $this->paging_format = '<ul class="pagination">%1$s %2$s %3$s</ul>'; // e.g. previous 1 2 3 4 5 next
        $this->paging_hide_prev_next = true; // hide prev / next links if they don't exist
        $this->paging_previous_text = 'Previous';
        $this->paging_next_text = 'Next';

        $this->prefix = $prefix ? $prefix . '_' : '';

        $this->hash_secret = 'djkla9uwekj.sd';

        //querystring
        $qs = $_GET;
        unset($qs[$this->prefix . 'page']);

        $this->str_ext_argv = http_build_query($qs);

        if (is_string($int_num_result) && str_contains($int_num_result, ',')) {
            $arr = explode(', ', $int_num_result);
            $this->page = $arr[0];
            $this->int_num_result = $arr[1];
        } else {
            $this->int_num_result = (is_numeric($int_num_result)) ? $int_num_result : null;
            $this->page = (int) $_GET[$this->prefix . 'page'];
        }

        if (is_numeric($_GET[$this->prefix . 'limit'])) {
            $this->int_num_result = $_GET[$this->prefix . 'limit'];
        } elseif ('All' == $_GET[$this->prefix . 'limit']) {
            $this->int_num_result = null;
        }

        if ($this->page < 0) {
            $this->page = 0;
        }

        $this->query = $query;

        if (
            $_GET[$this->prefix . 'order'] and
            $_GET[$this->prefix . 'hash'] and
            $_GET[$this->prefix . 'hash'] == md5($_GET[$this->prefix . 'order'] . $this->hash_secret)
        ) {
            $order = escape($_GET[$this->prefix . 'order']);
        }
        
        // enclose in backticks
        if (is_string($order) && str_contains($order, ' ') === false && str_contains($order, '.') === false && str_contains($order, '(') === false) {
            $order = '`'.$order.'`';
        }

        if (isset($_GET[$this->prefix . 'asc'])) {
            $asc = $_GET[$this->prefix . 'asc'];
        }

        if ($order and is_string($query)) {
            $this->query .= ' ORDER BY ' . $order;

            if (!$asc and ' DESC' !== substr($order, -5) and ' ASC' !== substr($order, -4)) {
                $this->query .= ' DESC';
            }

            $this->order = $order;
            $this->asc = $asc;
        }

        if ($this->int_num_result !== null and is_string($query)) {
            $this->query .= ' LIMIT ' . $this->page . ', ' . $this->int_num_result;
        }

        if (is_string($query)) {
            $this->query = trim($this->query);

            $pos = stripos($this->query, 'select');

            $this->query = substr($this->query, $pos + 6);
            $this->query = 'SELECT SQL_CALC_FOUND_ROWS ' . $this->query;

            $this->rows = sql_query($this->query);

            $count = sql_query('SELECT FOUND_ROWS()', 1);
            $this->total = current($count);
        } else {
            $this->total = count($query);
        }
    }

    /**
     * @return float|int
     */
    public function getNumberOfPages()
    {
        return $this->int_num_result ? ($this->total / $this->int_num_result) : 1;
    }

    /**
     * @return string
     */
    public function getCurrentPage()
    {
        $int_cur_page = ($this->page * $this->getNumberOfPages()) / $this->total;
        return number_format($int_cur_page, 0);
    }

    public function getPagingArray()
    {
        $array_paging['lower'] = ($this->page + 1);

        if ($this->page + $this->int_num_result >= $this->total) {
            $array_paging['upper'] = $this->total;
        } elseif ($this->int_num_result) {
            $array_paging['upper'] = ($this->page + $this->int_num_result);
        } else {
            $array_paging['upper'] = $this->total;
        }

        $array_paging['total'] = $this->total;

        $qs = $this->str_ext_argv ? '&' . $this->str_ext_argv : '';

        if (0 != $this->page) {
            $array_paging['previous_link'] = '?';
            if ($this->page - $this->int_num_result > 0) {
                $array_paging['previous_link'] .= $this->prefix . 'page=' . ($this->page - $this->int_num_result);
            }
            $array_paging['previous_link'] .= $qs;
            $array_paging['previous_tag'] = '<li class="page-item"><a class="page-link prev" href="' . $array_paging['previous_link'] . '">' . $this->paging_previous_text . '</a></li>';
        } else {
            $array_paging['previous_tag'] = '';
        }

        if ($this->int_num_result and (($this->total - $this->page) > $this->int_num_result)) {
            $int_new_position = $this->page + $this->int_num_result;
            $int_end = floor(($this->total / 10));
            $int_end *= 10;

            $array_paging['next_link'] = '?' . $this->prefix . 'page=' . $int_new_position . $qs;
            $array_paging['next_tag'] = '<li class="page-item"><a class="page-link next" href="' . $array_paging['next_link'] . '">' . $this->paging_next_text . '</a></li>';
        } else {
            $array_paging['next_tag'] = '';
        }
        return $array_paging;
    }

    /**
     * @return string[]
     */
    public function getPagingRowArray()
    {
        if ($this->getNumberOfPages() > 0) {
            $start = $this->getCurrentPage() - floor($this->num_pages / 2);

            if ($start < 0) {
                $start = 0;
            }

            $end = $start + $this->num_pages;

            if ($end > $this->getNumberOfPages()) {
                $end = $this->getNumberOfPages();
            }
        } else {
            $start = 0;
            $end = 0;
        }

        $array_all_page = [];
        $j = 0;
        for ($i = $start; $i < $end; $i++) {
            // if current page, do not make a link
            
            $class = $i == $this->getCurrentPage() ? ' active' : '';
            
            $int_new_position = ($i * $this->int_num_result);
            $link = '';
            if ($int_new_position > 0) {
                $link .= $this->prefix . 'page=' . $int_new_position;
            }

            $array_all_page[$j] = '<li class="page-item' . $class . '"><a class="page-link" href="?' . $link . '&' . $this->str_ext_argv . '">' . ($i + 1) . '</a></li>';
            $j++;
        }
        return $array_all_page;
    }

    /**
     * @return bool|string
     */
    public function get_paging()
    {
        if (($this->total < $this->int_num_result) or !$this->int_num_result) {
            return false;
        }

        // Load up the 2 array in order to display result
        $array_paging = $this->getPagingArray();
        $array_row_paging = $this->getPagingRowArray();

        $pages = '<ul class="pagination">';

        if ($array_paging['previous_tag']) {
            $pages .= $array_paging['previous_tag'];
        }
        
        if (sizeof($array_row_paging) > 1) {
            for ($i = 0; $i < sizeof($array_row_paging); $i++) {
                $pages .= $array_row_paging[$i];

                if (($i + 1) < sizeof($array_row_paging)) {
                    $pages .= $this->paging_separator;
                }
            }
        }

        if ($array_paging['next_tag']) {
            $pages .= $array_paging['next_tag'];
        }
        
        $pages .= '</ul>';
        
        return $pages;
    }

    /**
     * @param mixed $links
     * @return bool|string
     */
    public function get_results($links = false)
    {
        $array_paging = $this->getPagingArray();

        if (!$array_paging['total']) {
            return false;
        }

        // Display the result as you like...
        $paging = '';
        $paging .= '<strong>' . number_format($array_paging['lower']) . '</strong>';
        $paging .= ' - <strong>' . number_format($array_paging['upper']) . '</strong>';
        $paging .= ' of <strong>' . number_format($array_paging['total']) . '</strong>';

        if ($links) {
            // Load up the 2 array in order to display result
            $array_paging = $this->getPagingArray();
            //$array_row_paging = $this->getPagingRowArray();

            if ($array_paging['previous_tag']) {
                $paging = $array_paging['previous_tag'] . $paging;
            }

            if ($array_paging['next_tag']) {
                $paging .= $array_paging['next_tag'];
            }
        }

        return $paging;
    }

    /**
     * @return mixed[]
     */
    public function get_rows()
    {
        if (is_array($this->query)) {
            return array_slice($this->query, $this->page, $this->int_num_result);
        }
    }

    /**
     * @param mixed $col
     * @param null|mixed $label
     * @return string
     */
    public function col($col, $label = null)
    {
        $qs = $_GET;

        unset($qs['order']);
        unset($qs['asc']);
        unset($qs['hash']);

        $query = http_build_query($qs);

        if (!$label) {
            $label = $col;
        }

        $hash = md5($col . $this->hash_secret);

        if ($this->order == $col) {
            if (!$this->asc) {
                $html = '<a href="?' . $query . '&' . $this->prefix . 'order=' . $col . '&' . $this->prefix . 'asc=1&' . $this->prefix . 'hash=' . $hash . '">' . ($label) . '</a>';
                $html .= ' <i class="fa fa-chevron-up"></i>';
            } else {
                $html = '<a href="?' . $query . '&' . $this->prefix . 'order=' . $col . '&' . $this->prefix . 'asc=0&' . $this->prefix . 'hash=' . $hash . '">' . ($label) . '</a>';
                $html .= ' <i class="fa fa-chevron-down"></i>';
            }
        } else {
            $html = '<a href="?' . $query . '&' . $this->prefix . 'order=' . $col . '&' . $this->prefix . 'asc=1&' . $this->prefix . 'hash=' . $hash . '">' . ($label) . '</a>';
        }

        return $html;
    }

    /**
     * @param mixed $options
     * @return string
     */
    public function items_per_page($options = [25,50,100,200,'All'])
    {
        $qs = $_GET;

        unset($qs['hash']);

        $query = http_build_query($qs);

        $hash = md5($qs['order'] . $this->hash_secret);

        $html = 'Items per page ';

        foreach ($options as $v) {
            if ($v == $this->int_num_result) {
                $html .= $v . ' ';
            } else {
                $html .= '<a href="?' . $query . '&' . $this->prefix . 'limit=' . $v . '&' . $this->prefix . 'hash=' . $hash . '">' . $v . '</a> ';
            }
        }

        return $html;
    }
}
