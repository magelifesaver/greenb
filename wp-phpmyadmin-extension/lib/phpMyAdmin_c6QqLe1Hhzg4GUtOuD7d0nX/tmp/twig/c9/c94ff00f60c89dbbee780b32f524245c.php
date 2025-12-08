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

/* database/search/results.twig */
class __TwigTemplate_0634965f7fc9fe05dd10091cf83f77aa extends Template
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
        yield "<table class=\"table table-striped caption-top w-auto\">
    <caption class=\"tblHeaders\">
        ";
        // line 3
        yield Twig\Extension\CoreExtension::sprintf("Search results for \"<em>%s</em>\" %s:",         // line 4
($context["criteria_search_string"] ?? null),         // line 5
($context["search_type_description"] ?? null));
        // line 6
        yield "
    </caption>
    ";
        // line 8
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["rows"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 9
            yield "        <tr class=\"noclick\">
            <td>
                ";
            // line 11
            $context["result_message"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
                // line 12
                yield "                    ";
yield _ngettext("%1\$s match in <strong>%2\$s</strong>", "%1\$s matches in <strong>%2\$s</strong>", abs(CoreExtension::getAttribute($this->env, $this->source,                 // line 14
$context["row"], "result_count", [], "any", false, false, false, 14)));
                // line 17
                yield "                ";
                return; yield '';
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 18
            yield "                ";
            yield Twig\Extension\CoreExtension::sprintf(($context["result_message"] ?? null), CoreExtension::getAttribute($this->env, $this->source, $context["row"], "result_count", [], "any", false, false, false, 18), CoreExtension::getAttribute($this->env, $this->source, $context["row"], "table", [], "any", false, false, false, 18));
            yield "
            </td>
            ";
            // line 20
            if ((CoreExtension::getAttribute($this->env, $this->source, $context["row"], "result_count", [], "any", false, false, false, 20) > 0)) {
                // line 21
                yield "                ";
                $context["url_params"] = ["db" =>                 // line 22
($context["db"] ?? null), "table" => CoreExtension::getAttribute($this->env, $this->source,                 // line 23
$context["row"], "table", [], "any", false, false, false, 23), "goto" => PhpMyAdmin\Url::getFromRoute("/database/sql"), "pos" => 0, "is_js_confirmed" => 0];
                // line 28
                yield "                <td>
                    <a name=\"browse_search\"
                        class=\"ajax browse_results\"
                        href=\"";
                // line 31
                yield PhpMyAdmin\Url::getFromRoute("/sql", ($context["url_params"] ?? null));
                yield "\"
                        data-browse-sql=\"";
                // line 32
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["row"], "new_search_sqls", [], "any", false, false, false, 32), "select_columns", [], "any", false, false, false, 32), "html", null, true);
                yield "\"
                        data-table-name=\"";
                // line 33
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["row"], "table", [], "any", false, false, false, 33), "html", null, true);
                yield "\">
                        ";
yield _gettext("Browse");
                // line 35
                yield "                    </a>
                </td>
                <td>
                    <a name=\"delete_search\"
                        class=\"ajax delete_results\"
                        href=\"";
                // line 40
                yield PhpMyAdmin\Url::getFromRoute("/sql", ($context["url_params"] ?? null));
                yield "\"
                        data-delete-sql=\"";
                // line 41
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["row"], "new_search_sqls", [], "any", false, false, false, 41), "delete", [], "any", false, false, false, 41), "html", null, true);
                yield "\"
                        data-table-name=\"";
                // line 42
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["row"], "table", [], "any", false, false, false, 42), "html", null, true);
                yield "\">
                        ";
yield _gettext("Delete");
                // line 44
                yield "                    </a>
                </td>
            ";
            } else {
                // line 47
                yield "                <td></td>
                <td></td>
            ";
            }
            // line 50
            yield "        </tr>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 52
        yield "</table>

";
        // line 54
        if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["criteria_tables"] ?? null)) > 1)) {
            // line 55
            yield "    <p>
        ";
yield strtr(_ngettext("<strong>Total:</strong> <em>%count%</em> match", "<strong>Total:</strong> <em>%count%</em> matches", abs(            // line 58
($context["result_total"] ?? null))), array("%count%" => abs(($context["result_total"] ?? null)), "%count%" => abs(($context["result_total"] ?? null)), ));
            // line 61
            yield "    </p>
";
        }
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "database/search/results.twig";
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
        return array (  147 => 61,  145 => 58,  142 => 55,  140 => 54,  136 => 52,  129 => 50,  124 => 47,  119 => 44,  114 => 42,  110 => 41,  106 => 40,  99 => 35,  94 => 33,  90 => 32,  86 => 31,  81 => 28,  79 => 23,  78 => 22,  76 => 21,  74 => 20,  68 => 18,  64 => 17,  62 => 14,  60 => 12,  58 => 11,  54 => 9,  50 => 8,  46 => 6,  44 => 5,  43 => 4,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/search/results.twig", "/chroot/home/a2ea4401/73e3dceaec.nxcli.io/html/wp-content/plugins/wp-phpmyadmin-extension/lib/phpMyAdmin_c6QqLe1Hhzg4GUtOuD7d0nX/templates/database/search/results.twig");
    }
}
