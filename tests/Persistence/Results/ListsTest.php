<?php

namespace Test\Lucinda\Framework\Persistence\Results;

use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Results\Lists;
use Lucinda\UnitTest\Result;

final class ListsTest
{
    public function getFirstRow(): array
    {
        $selection = new Lists([['id' => '42'], ['id' => '7']]);
        return [
            new Result((new Lists([]))->getFirstRow() === null, 'empty selection has no first row'),
            new Result($selection->getFirstRow()->getInteger('id') === 42, 'first row follows database order'),
            new Result($selection->getFirstRow() === $selection->getRows()[0], 'first row is the retained projection'),
        ];
    }

    public function getRows(): array
    {
        $input = [['id' => '42', 'name' => null], ['id' => '7', 'name' => 'second']];
        $selection = new Lists($input);
        $input[0]['id'] = '999';
        $rows = $selection->getRows();
        $results = [
            new Result((new Lists([]))->getRows() === [], 'empty selection returns an empty list'),
            new Result(count($rows) === 2 && $rows[0] instanceof Row && $rows[1] instanceof Row, 'projections become typed rows'),
            new Result($rows[0]->getInteger('id') === 42 && $rows[1]->getInteger('id') === 7, 'rows retain database order and captured values'),
            new Result($rows[0]->getNullableString('name') === null && $rows[1]->getString('name') === 'second', 'column values survive materialization'),
        ];
        array_pop($rows);
        $results[] = new Result(count($selection->getRows()) === 2, 'changing the returned list does not remove retained rows');
        return $results;
    }

    public function isEmpty(): array
    {
        return [
            new Result((new Lists([]))->isEmpty(), 'no selected rows is empty'),
            new Result(!(new Lists([[]]))->isEmpty(), 'a row without columns is still a selected row'),
            new Result(!(new Lists([['id' => 1], ['id' => 2]]))->isEmpty(), 'multiple selected rows are not empty'),
        ];
    }

}
