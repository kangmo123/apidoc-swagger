<?php

namespace App\Services\Tree;

interface ITreeNode extends \JsonSerializable
{
    public function getTaskHash();

    public function setExtraData(array $extraData);

    public function getExtraData();
}
