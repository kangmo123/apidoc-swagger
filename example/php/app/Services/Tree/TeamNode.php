<?php

namespace App\Services\Tree;

use Illuminate\Support\Collection;

class TeamNode implements ITreeNode
{
    /**
     * @var TeamNode|null
     */
    protected $parent = null;

    /**
     * @var Collection|TeamNode[]
     */
    protected $children = null;

    /**
     * @var Collection|SaleNode[]
     */
    protected $sales = null;

    /**
     * @var SaleNode|null
     */
    protected $leader = null;

    protected $id = 0;

    protected $teamId = '';

    protected $code = '';

    protected $name = '';

    protected $level = 0;

    protected $pid = '';

    protected $type = 0;

    protected $begin = '';

    protected $end = '';

    protected $sort = 0;

    protected $extraData = [];

    /**
     * 标记team node
     * 用于销售角色查看任务or业绩的时候
     * 确定销售在组织架构树上哪些节点有权限
     * @var bool
     */
    protected $marked = false;

    public function __construct()
    {
        $this->children = collect([]);
        $this->sales = collect([]);
    }

    /**
     * @return TeamNode|null
     */
    public function getParent(): ?TeamNode
    {
        return $this->parent;
    }

    /**
     * @param TeamNode|null $parent
     */
    public function setParent(?TeamNode $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return TeamNode[]|Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param TeamNode[]|Collection $children
     */
    public function setChildren($children): void
    {
        $this->children = $children;
    }

    /**
     * @param TeamNode $child
     */
    public function addChild($child)
    {
        $this->children->push($child);
    }

    /**
     * @return SaleNode[]|Collection
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * @param SaleNode[]|Collection $sales
     */
    public function setSales($sales): void
    {
        $this->sales = $sales;
    }

    /**
     * @param SaleNode $sale
     */
    public function addSale($sale): void
    {
        $this->sales->push($sale);
    }

    /**
     * @return SaleNode|null
     */
    public function getLeader(): ?SaleNode
    {
        return $this->leader;
    }

    /**
     * @param SaleNode|null $leader
     */
    public function setLeader(?SaleNode $leader): void
    {
        $this->leader = $leader;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTeamId(): string
    {
        return $this->teamId;
    }

    /**
     * @param string $teamId
     */
    public function setTeamId(string $teamId): void
    {
        $this->teamId = $teamId;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getPid(): string
    {
        return $this->pid;
    }

    /**
     * @param string $pid
     */
    public function setPid(string $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getBegin(): string
    {
        return $this->begin;
    }

    /**
     * @param string $begin
     */
    public function setBegin(string $begin): void
    {
        $this->begin = $begin;
    }

    /**
     * @return string
     */
    public function getEnd(): string
    {
        return $this->end;
    }

    /**
     * @param string $end
     */
    public function setEnd(string $end): void
    {
        $this->end = $end;
    }

    /**
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array $extraData
     */
    public function setExtraData(array $extraData): void
    {
        $this->extraData = $extraData;
    }

    /**
     * @return bool
     */
    public function isMarked(): bool
    {
        return $this->marked;
    }

    /**
     * @param bool $marked
     */
    public function setMarked(bool $marked): void
    {
        $this->marked = $marked;
    }

    /**
     * 设置本节点mark状态，并且设置全部的子TeamNode状态
     */
    public function mark()
    {
        if ($this->isMarked()) {
            return;
        }
        $this->setMarked(true);
        $children = $this->getChildren();
        foreach ($children as $child) {
            $child->mark();
        }
    }

    public function getTaskHash()
    {
        return $this->getTeamId() . "|" . $this->getPid();
    }

    public function jsonSerialize()
    {
        if ($this->getLeader()) {
            $name = $this->getName() . "-" . $this->getLeader()->getFullname();
        } else {
            $name = $this->getName();
        }
        $data = [
            'id' => $this->id,
            'team_id' => $this->teamId,
            'code' => $this->code,
            'name' => $name,
            'pid' => $this->pid,
            'level' => $this->level,
            'type' => $this->type,
            'begin' => $this->begin,
            'end' => $this->end,
            'sort' => $this->sort,
            'leader' => $this->leader,
        ];
        if ($this->children->isNotEmpty()) {
            $data['children'] = $this->children->sort(function ($a, $b) {
                return $a->getSort() >= $b->getSort();
            })->values();
        } else {
            $data['children'] = $this->sales;
        }
        foreach ($this->extraData as $k => $v) {
            if (!array_key_exists($k, $data)) {
                $data[$k] = $v;
            }
        }
        return $data;
    }
}
