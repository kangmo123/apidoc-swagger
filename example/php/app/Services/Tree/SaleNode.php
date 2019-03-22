<?php

namespace App\Services\Tree;

class SaleNode implements ITreeNode
{
    /**
     * @var TeamNode
     */
    protected $parent = null;

    protected $id = 0;

    protected $saleId = '';

    protected $rtx = '';

    protected $name = '';

    protected $fullname = '';

    protected $mobile = '';

    protected $email = '';

    protected $enable = 0;

    protected $hireDate = '';

    protected $leaveDate = '';

    protected $deleteDate = '';

    protected $tofStatus = 0;

    protected $isOwner = 0;

    protected $pid = '';

    protected $date = [];

    protected $extraData = [];

    /**
     * @return TeamNode
     */
    public function getParent(): TeamNode
    {
        return $this->parent;
    }

    /**
     * @param TeamNode $parent
     */
    public function setParent(TeamNode $parent): void
    {
        $this->parent = $parent;
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
    public function getSaleId(): string
    {
        return $this->saleId;
    }

    /**
     * @param string $saleId
     */
    public function setSaleId(string $saleId): void
    {
        $this->saleId = $saleId;
    }

    /**
     * @return string
     */
    public function getRtx(): string
    {
        return $this->rtx;
    }

    /**
     * @param string $rtx
     */
    public function setRtx(string $rtx): void
    {
        $this->rtx = $rtx;
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
     * @return string
     */
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * @param string $fullname
     */
    public function setFullname(string $fullname): void
    {
        $this->fullname = $fullname;
    }

    /**
     * @return string
     */
    public function getMobile(): string
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getEnable(): int
    {
        return $this->enable;
    }

    /**
     * @param int $enable
     */
    public function setEnable(int $enable): void
    {
        $this->enable = $enable;
    }

    /**
     * @return string
     */
    public function getHireDate(): string
    {
        return $this->hireDate;
    }

    /**
     * @param string $hireDate
     */
    public function setHireDate(string $hireDate): void
    {
        $this->hireDate = $hireDate;
    }

    /**
     * @return string
     */
    public function getLeaveDate(): string
    {
        return $this->leaveDate;
    }

    /**
     * @param string $leaveDate
     */
    public function setLeaveDate(string $leaveDate): void
    {
        $this->leaveDate = $leaveDate;
    }

    /**
     * @return string
     */
    public function getDeleteDate(): string
    {
        return $this->deleteDate;
    }

    /**
     * @param string $deleteDate
     */
    public function setDeleteDate(string $deleteDate): void
    {
        $this->deleteDate = $deleteDate;
    }

    /**
     * @return int
     */
    public function getTofStatus(): int
    {
        return $this->tofStatus;
    }

    /**
     * @param int $tofStatus
     */
    public function setTofStatus(int $tofStatus): void
    {
        $this->tofStatus = $tofStatus;
    }

    /**
     * @return int
     */
    public function getIsOwner(): int
    {
        return $this->isOwner;
    }

    /**
     * @param int $isOwner
     */
    public function setIsOwner(int $isOwner): void
    {
        $this->isOwner = $isOwner;
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
     * @return array
     */
    public function getDate(): array
    {
        return $this->date;
    }

    /**
     * @param array $date
     */
    public function setDate(array $date): void
    {
        $this->date = $date;
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

    public function getTaskHash()
    {
        return $this->getSaleId() . "|" . $this->getPid();
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'sale_id' => $this->saleId,
            "rtx" => $this->rtx,
            'name' => $this->fullname,
            "cname" => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            "enable" => $this->enable,
            "hire_date" => $this->hireDate,
            "leave_date" => $this->leaveDate,
            'delete_date' => $this->deleteDate,
            'tof_status' => $this->tofStatus,
            'is_owner' => $this->isOwner,
            'pid' => $this->pid,
            'date' => $this->date,
        ];
        foreach ($this->extraData as $k => $v) {
            if (!array_key_exists($k, $data)) {
                $data[$k] = $v;
            }
        }
        return $data;
    }
}
