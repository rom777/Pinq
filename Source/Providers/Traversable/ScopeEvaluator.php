<?php

namespace Pinq\Providers\Traversable;

use \Pinq\Queries\Segments;

/**
 * Evaluates the query scope against the supplied traversable
 * 
 * @author Elliot Levin <elliot@aanet.com.au>
 */
class ScopeEvaluator extends Segments\SegmentVisitor
{
    /**
     * @var \Pinq\ITraversable
     */
    protected $Traversable;

    final public function SetTraversable(\Pinq\ITraversable $Traversable)
    {
        $this->Traversable = $Traversable;
    }

    /**
     * @return \Pinq\ITraversable
     */
    public function GetTraversable()
    {
        return $this->Traversable;
    }
    
    public function VisitFilter(Segments\Filter $Query)
    {
        $this->Traversable = $this->Traversable->Where($Query->GetFunctionExpressionTree());
    }

    public function VisitRange(Segments\Range $Query)
    {
        $this->Traversable = $this->Traversable->Slice($Query->GetRangeStart(), $Query->GetRangeAmount());
    }

    public function VisitSelect(Segments\Select $Query)
    {
        $this->Traversable = $this->Traversable->Select($Query->GetFunctionExpressionTree());
    }

    public function VisitSelectMany(Segments\SelectMany $Query)
    {
        $this->Traversable = $this->Traversable->SelectMany($Query->GetFunctionExpressionTree());
    }

    public function VisitUnique(Segments\Unique $Query)
    {
        $this->Traversable = $this->Traversable->Unique();
    }

    public function VisitOrderBy(Segments\OrderBy $Query)
    {
        $IsAscendingArray = $Query->GetIsAscendingArray();
        $First = true;
        foreach($Query->GetFunctionExpressionTrees() as $Key => $FunctionExpressionTree) {
            
            $Direction = $IsAscendingArray[$Key] ? \Pinq\Direction::Ascending : \Pinq\Direction::Descending;
            
            if($First) {
                $this->Traversable = $this->Traversable->OrderBy($FunctionExpressionTree, $Direction);
                $First = false;
            }
            else {
                $this->Traversable = $this->Traversable->ThenBy($FunctionExpressionTree, $Direction);
            }
        }
    }

    public function VisitGroupBy(Segments\GroupBy $Query)
    {
        $First = true;
        foreach($Query->GetFunctionExpressionTrees() as $FunctionExpressionTree) {
            
            $this->Traversable = 
                    $First ?
                    $this->Traversable->GroupBy($FunctionExpressionTree) :
                    $this->Traversable->AndBy($FunctionExpressionTree);
            if($First) {
                $First = false;
            }
        }
    }
    
    public function VisitJoin(Segments\Join $Query)
    {
        $this->Traversable = $this->GetJoin($Query)
                ->On($Query->GetOnFunctionExpressionTree())
                ->To($Query->GetJoiningFunctionExpressionTree());
    }
    
    public function VisitEqualityJoin(Segments\EqualityJoin $Query)
    {
        $this->Traversable = $this->GetJoin($Query)
                ->OnEquality($Query->GetOuterKeyFunctionExpressionTree(), $Query->GetInnerKeyFunctionExpressionTree())
                ->To($Query->GetJoiningFunctionExpressionTree());
    }
    
    private function GetJoin(Segments\JoinBase $Query)
    {
         if($Query->IsGroupJoin()) {
            return $this->Traversable->GroupJoin($Query->GetValues());
        }
        else {
            return $this->Traversable->Join($Query->GetValues());
        }
    }

    protected function VisitIndexBy(Segments\IndexBy $Query)
    {
        $this->Traversable = $this->Traversable->IndexBy($Query->GetFunctionExpressionTree());
    }

    public function VisitOperation(Segments\Operation $Query)
    {
        $OtherTraversable = $Query->GetTraversable();
        switch ($Query->GetOperationType()) {
            case Segments\Operation::Union:
                $this->Traversable = $this->Traversable->Union($OtherTraversable);
                break;
            case Segments\Operation::Intersect:
                $this->Traversable = $this->Traversable->Intersect($OtherTraversable);
                break;
            case Segments\Operation::Difference:
                $this->Traversable = $this->Traversable->Difference($OtherTraversable);
                break;
            
            case Segments\Operation::Append:
                $this->Traversable = $this->Traversable->Append($OtherTraversable);
                break;
            case Segments\Operation::WhereIn:
                $this->Traversable = $this->Traversable->WhereIn($OtherTraversable);
                break;
            case Segments\Operation::Except:
                $this->Traversable = $this->Traversable->Except($OtherTraversable);
                break;
        }
    }

}
