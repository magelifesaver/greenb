<?php

namespace Kestrel\Account_Funds\Scoped\Doctrine\Common\Collections\Expr;

/**
 * Expression for the {@link Selectable} interface.
 */
interface Expression
{
    /** @return mixed */
    public function visit(ExpressionVisitor $visitor);
}
