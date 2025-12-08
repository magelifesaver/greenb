<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* table/search/column_comparison_operators.twig */
class __TwigTemplate_4ed15728b1b92b077a7dfe2fda0c5de2 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        yield "<select class=\"column-operator\" id=\"ColumnOperator";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search_index"] ?? null), "html", null, true);
        yield "\" name=\"criteriaColumnOperators[";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["search_index"] ?? null), "html", null, true);
        yield "]\">
    ";
        // line 2
        yield ($context["type_operators"] ?? null);
        yield "
</select>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "table/search/column_comparison_operators.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  45 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/search/column_comparison_operators.twig", "/chroot/home/a2ea4401/73e3dceaec.nxcli.io/html/wp-content/plugins/wp-phpmyadmin-extension/lib/phpMyAdmin_c6QqLe1Hhzg4GUtOuD7d0nX/templates/table/search/column_comparison_operators.twig");
    }
}
