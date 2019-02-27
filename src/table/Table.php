<?php
/**
 * Created by PhpStorm.
 * User: yangyuance
 * Date: 2019/1/30
 * Time: 下午4:04
 */

namespace Yirius\Admin\table;


use think\Collection;
use Yirius\Admin\Admin;
use Yirius\Admin\form\Form;
use Yirius\Admin\Layout;
use Yirius\Admin\layout\Card;
use Yirius\Admin\layout\PageView;
use Yirius\Admin\table\events\Tool;

/**
 * Class Table
 *
 * @method Table setUrl($url);
 * @method Table setToolbar($toolbar);
 * @method Table setWidth($width);
 * @method Table setHeight($height);
 * @method Table setCellMinWidth($width);
 * @method Table setData($data);
 * @method Table setTotalRow($isTotal = true);
 * @method Table setPage($isPage = true);
 * @method Table setLimit($limit = 10);
 * @method Table setLimits(array $limits);
 * @method Table setLoading($loading = true);
 * @method Table setTitle($title);
 * @method Table setText($text);
 * @method Table setAutoSort($isSort = true);
 *
 * @method Table getUrl();
 * @method Table getToolbar();
 * @method Table getWidth();
 * @method Table getHeight();
 * @method Table getCellMinWidth();
 * @method Table getData();
 * @method Table getTotalRow();
 * @method Table getPage();
 * @method Table getLimit();
 * @method Table getLimits();
 * @method Table getLoading();
 * @method Table getTitle();
 * @method Table getText();
 * @method Table getAutoSort();
 *
 * @package Yirius\Admin\table
 */
class Table extends Layout
{
    /**
     * Table's name
     * @var string
     */
    protected $name = '';

    /**
     * @var Form
     */
    protected $search = null;

    /**
     * @var array
     */
    protected $config = [
        'defaultToolbar' => ['refresh', 'filter', 'print', 'exports']
    ];

    /**
     * Columns list
     * @var array
     */
    protected $columns = [];

    /**
     * @var Tool
     */
    protected $tool = null;

    /**
     * @var Toolbar
     */
    protected $toolbar = null;

    /**
     * restful url
     * @var string
     */
    protected $restfulUrl = null;

    /**
     * edit view path
     * @var string
     */
    protected $editPath = null;

    /**
     * Table constructor.
     * @param $name
     * @param \Closure|null $callback
     */
    public function __construct($name, \Closure $callback = null)
    {
        $this->setName($name);

        if ($callback instanceof \Closure) {
            call($callback, [$this]);
        }
    }

    /**
     * @title search
     * @description set table's search
     * @createtime 2019/2/26 下午3:27
     * @param \Closure $callback
     * @return Form
     * @throws \Exception
     */
    public function search(\Closure $callback)
    {
        $this->search = (new Form($this->getName() . '_form', function(Form $form) use($callback){
            $form->inline($callback)
                ->button("", '<i class="layui-icon layui-icon-search thinkeradmin-button-btn"></i>')
                ->setAttributes('lay-submit', '')
                ->setId($this->getName() . '_form_search');
        }));

        return $this->search;
    }

    /**
     * @title columns
     * @description
     * @createtime 2019/2/26 下午3:56
     * @param $field
     * @param $name
     * @return Columns
     */
    public function columns($field, $name)
    {
        $columns = (new Columns($field, $name));

        $columns->setToolbar($this->getName() . "_". $field ."_tool");

        $this->columns[] = $columns;

        return $columns;
    }

    /**
     * @title toolbar
     * @description tool's event
     * @createtime 2019/2/26 下午7:00
     * @param \Closure|null $callback
     * @return string|Tool
     */
    public function tool(\Closure $callback = null)
    {
        $this->tool = (new Tool($this, $callback));

        return $this->tool;
    }

    /**
     * @title toolbar
     * @description
     * @createtime 2019/2/27 上午12:37
     * @param \Closure|null $callback
     * @return Toolbar
     */
    public function toolbar(\Closure $callback = null)
    {
        $this->toolbar = (new Toolbar($this, $callback));

        return $this->toolbar;
    }

    /**
     * @title render
     * @description use for render each type
     * @createtime 2019/2/26 下午3:41
     * @return mixed|string
     * @throws \Exception
     */
    public function render()
    {
        //judge table server url
        if(empty($this->config['url']))
        {
            throw new \Exception("table config's field must have [url]");
        }

        //render columns
        $columns = [];
        $columnsTool = [];
        foreach($this->columns as $i => $v){
            $columns[] = $v->render();
            $columnsTool[] = $v->getTool();
        }
        //table's config cols
        $columns = json_encode($columns);
        //use tools html string
        $columnsTool = join("", $columnsTool);

        //get tool's event
        $toolEvent = empty($this->tool) ? '' : $this->tool->render();

        //get table's toolbar
        if(empty($this->toolbar)){
            $toolbar = '';
        }else{
            $toolbar = $this->toolbar->render();
            $this->setToolbar("#" . $this->getName() . "_toolbar");
        }

        Admin::script('table', 2);

        Admin::script(<<<HTML
var _searchField = {};
layui.form.on('submit({$this->getName()}_form_search)', function (data) {
    _searchField = data.field;
    //执行重载
    layui.table.reload('{$this->getName()}', {
        where: _searchField
    });
});
var _{$this->getName()}_ins = layui.table.render($.extend({
    elem: "#{$this->getName()}",
    where: layui.http._beforeAjax({}),
    response: {statusCode: 1},
    page: true,
    toolbar: true,
    cols: [{$columns}]
}, {$this->getConfig()}));
{$toolEvent}
HTML
        );

        return <<<HTML
{$toolbar}
{$columnsTool}
<table id="{$this->getName()}" lay-filter="{$this->getName()}"></table>
HTML
            ;
    }

    /**
     * @title show
     * @description
     * @createtime 2019/2/26 下午3:14
     * @return mixed
     */
    public function show()
    {
        return Admin::pageView(function (PageView $pageView) {
            $pageView->card(function(Card $card){
                if(!is_null($this->search)){
                    $card
                        ->setTitle($this->search->render())
                        ->setHeaderClass('thinkeradmin-card-header-auto');
                }
                $card->setContent($this->render());
            });
        })->render();
    }

    /**
     * @title setName
     * @description
     * @createtime 2019/2/26 下午3:15
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = str_replace(["'", '"', ' ', '.', '。', ',', '，', ':', '：', '/', '、'], "_", $name);

        $this->config['toolbar'] = "#". $this->getName() ."_toolbar";

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @title setConfig
     * @description
     * @createtime 2019/2/26 下午3:34
     * @param $field
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    public function setConfig($field, $value = null)
    {
        if (is_null($value)) {
            if (is_array($field)) {
                $this->config = array_merge($this->config, $field);
            } else {
                throw new \Exception("table config's field must be array when value is null");
            }
        } else {
            $this->config[$field] = $value;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return json_encode($this->config);
    }

    /**
     * @title setRestfulUrl
     * @description
     * @createtime 2019/2/27 下午2:52
     * @param $restfulUrl
     * @return $this
     */
    public function setRestfulUrl($restfulUrl)
    {
        $this->restfulUrl = $restfulUrl;

        $this->setUrl($restfulUrl);

        return $this;
    }

    /**
     * @return string
     */
    public function getRestfulUrl()
    {
        return $this->restfulUrl;
    }


    /**
     * @title setEditPath
     * @description
     * @createtime 2019/2/27 下午2:53
     * @param $editPath
     * @return $this
     */
    public function setEditPath($editPath)
    {
        $this->editPath = $editPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getEditPath()
    {
        return $this->editPath;
    }

    /**
     * @title __call
     * @description
     * @createtime 2019/2/26 下午5:25
     * @param $name
     * @param $arguments
     * @return $this|mixed|string
     */
    public function __call($name, $arguments)
    {
        $operateType = substr($name, 0, 3);
        $firstChar = substr($name, 3, 1);
        $name = strtolower($firstChar) . substr($name, 4);

        //if it is set
        if($operateType === "set"){

            $this->config[$name] = $arguments[0];

            return $this;

        }else if($operateType === "get"){

            return empty($this->config[$name]) ? '' : $this->config[$name];

        }

        return $this;
    }
}