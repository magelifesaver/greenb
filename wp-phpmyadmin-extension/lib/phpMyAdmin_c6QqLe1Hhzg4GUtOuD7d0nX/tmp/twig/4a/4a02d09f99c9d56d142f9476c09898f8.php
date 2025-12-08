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

/* database/events/editor_form.twig */
class __TwigTemplate_bc24c0343ac38879d0a086d48f5f37d2 extends Template
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
        yield "<form class=\"rte_form\" action=\"";
        yield PhpMyAdmin\Url::getFromRoute("/database/events");
        yield "\" method=\"post\">
  ";
        // line 2
        yield PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        yield "
  <input name=\"";
        // line 3
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["mode"] ?? null), "html", null, true);
        yield "_item\" type=\"hidden\" value=\"1\">
  ";
        // line 4
        if ((($context["mode"] ?? null) == "edit")) {
            // line 5
            yield "    <input name=\"item_original_name\" type=\"hidden\" value=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_original_name", [], "any", false, false, false, 5), "html", null, true);
            yield "\">
  ";
        }
        // line 7
        yield "
  <div class=\"card\">
    <div class=\"card-header\">
      ";
yield _gettext("Details");
        // line 11
        yield "      ";
        if ((($context["mode"] ?? null) != "edit")) {
            // line 12
            yield "        ";
            yield PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_EVENT");
            yield "
      ";
        }
        // line 14
        yield "    </div>

    <div class=\"card-body\">
      <table class=\"rte_table table table-borderless table-sm\">
        <tr>
          <td>";
yield _gettext("Event name");
        // line 19
        yield "</td>
          <td>
            <input type=\"text\" name=\"item_name\" value=\"";
        // line 21
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_name", [], "any", false, false, false, 21), "html", null, true);
        yield "\" maxlength=\"64\">
          </td>
        </tr>
        <tr>
          <td>";
yield _gettext("Status");
        // line 25
        yield "</td>
          <td>
            <select name=\"item_status\">
              ";
        // line 28
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["status_display"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["status"]) {
            // line 29
            yield "                <option value=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["status"], "html", null, true);
            yield "\"";
            yield ((($context["status"] == CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_status", [], "any", false, false, false, 29))) ? (" selected") : (""));
            yield ">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["status"], "html", null, true);
            yield "</option>
              ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['status'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 31
        yield "            </select>
          </td>
        </tr>
        <tr>
          <td>";
yield _gettext("Event type");
        // line 35
        yield "</td>
          <td>
            ";
        // line 37
        if (($context["is_ajax"] ?? null)) {
            // line 38
            yield "              <select name=\"item_type\">
                ";
            // line 39
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["event_type"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["type"]) {
                // line 40
                yield "                  <option value=\"";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["type"], "html", null, true);
                yield "\"";
                yield ((($context["type"] == CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 40))) ? (" selected") : (""));
                yield ">";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["type"], "html", null, true);
                yield "</option>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['type'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 42
            yield "              </select>
            ";
        } else {
            // line 44
            yield "              <input name=\"item_type\" type=\"hidden\" value=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 44), "html", null, true);
            yield "\">
              <div class=\"fw-bold text-center w-50\">
                ";
            // line 46
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 46), "html", null, true);
            yield "
              </div>
              <input type=\"submit\" name=\"item_changetype\" class=\"w-50\" value=\"";
            // line 48
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(Twig\Extension\CoreExtension::sprintf(_gettext("Change to %s"), CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type_toggle", [], "any", false, false, false, 48)), "html", null, true);
            yield "\">
            ";
        }
        // line 50
        yield "          </td>
        </tr>
        <tr class=\"onetime_event_row";
        // line 52
        yield (((CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 52) != "ONE TIME")) ? (" hide") : (""));
        yield "\">
          <td>";
yield _gettext("Execute at");
        // line 53
        yield "</td>
          <td class=\"text-nowrap\">
            <input type=\"text\" name=\"item_execute_at\" value=\"";
        // line 55
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_execute_at", [], "any", false, false, false, 55), "html", null, true);
        yield "\" class=\"datetimefield\">
          </td>
        </tr>
        <tr class=\"recurring_event_row";
        // line 58
        yield (((CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 58) != "RECURRING")) ? (" hide") : (""));
        yield "\">
          <td>";
yield _gettext("Execute every");
        // line 59
        yield "</td>
          <td>
            <input class=\"w-50\" type=\"text\" name=\"item_interval_value\" value=\"";
        // line 61
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_interval_value", [], "any", false, false, false, 61), "html", null, true);
        yield "\">
            <select class=\"w-50\" name=\"item_interval_field\">
              ";
        // line 63
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["event_interval"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["interval"]) {
            // line 64
            yield "                <option value=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["interval"], "html", null, true);
            yield "\"";
            yield ((($context["interval"] == CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_interval_field", [], "any", false, false, false, 64))) ? (" selected") : (""));
            yield ">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["interval"], "html", null, true);
            yield "</option>
              ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['interval'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 66
        yield "            </select>
          </td>
        </tr>
        <tr class=\"recurring_event_row";
        // line 69
        yield (((CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 69) != "RECURRING")) ? (" hide") : (""));
        yield "\">
          <td>";
yield _pgettext("Start of recurring event", "Start");
        // line 70
        yield "</td>
          <td class=\"text-nowrap\">
            <input type=\"text\" name=\"item_starts\" value=\"";
        // line 72
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_starts", [], "any", false, false, false, 72), "html", null, true);
        yield "\" class=\"datetimefield\">
          </td>
        </tr>
        <tr class=\"recurring_event_row";
        // line 75
        yield (((CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_type", [], "any", false, false, false, 75) != "RECURRING")) ? (" hide") : (""));
        yield "\">
          <td>";
yield _pgettext("End of recurring event", "End");
        // line 76
        yield "</td>
          <td class=\"text-nowrap\">
            <input type=\"text\" name=\"item_ends\" value=\"";
        // line 78
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_ends", [], "any", false, false, false, 78), "html", null, true);
        yield "\" class=\"datetimefield\">
          </td>
        </tr>
        <tr>
          <td>";
yield _gettext("Definition");
        // line 82
        yield "</td>
          <td>
            <textarea name=\"item_definition\" rows=\"15\" cols=\"40\">";
        // line 85
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_definition", [], "any", false, false, false, 85), "html", null, true);
        // line 86
        yield "</textarea>
          </td>
        </tr>
        <tr>
          <td>";
yield _gettext("On completion preserve");
        // line 90
        yield "</td>
          <td>
            <input type=\"checkbox\" name=\"item_preserve\"";
        // line 92
        yield CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_preserve", [], "any", false, false, false, 92);
        yield ">
          </td>
        </tr>
        <tr>
          <td>";
yield _gettext("Definer");
        // line 96
        yield "</td>
          <td>
            <input type=\"text\" name=\"item_definer\" value=\"";
        // line 98
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_definer", [], "any", false, false, false, 98), "html", null, true);
        yield "\">
          </td>
        </tr>
        <tr>
          <td>";
yield _gettext("Comment");
        // line 102
        yield "</td>
          <td>
            <input type=\"text\" name=\"item_comment\" value=\"";
        // line 104
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "item_comment", [], "any", false, false, false, 104), "html", null, true);
        yield "\" maxlength=\"64\">
          </td>
        </tr>
      </table>
    </div>

    ";
        // line 110
        if (($context["is_ajax"] ?? null)) {
            // line 111
            yield "      <input type=\"hidden\" name=\"editor_process_";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["mode"] ?? null), "html", null, true);
            yield "\" value=\"true\">
      <input type=\"hidden\" name=\"ajax_request\" value=\"true\">
    ";
        } else {
            // line 114
            yield "      <div class=\"card-footer\">
        <input class=\"btn btn-primary\" type=\"submit\" name=\"editor_process_";
            // line 115
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["mode"] ?? null), "html", null, true);
            yield "\" value=\"";
yield _gettext("Go");
            yield "\">
      </div>
    ";
        }
        // line 118
        yield "  </div>
</form>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "database/events/editor_form.twig";
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
        return array (  326 => 118,  318 => 115,  315 => 114,  308 => 111,  306 => 110,  297 => 104,  293 => 102,  285 => 98,  281 => 96,  273 => 92,  269 => 90,  262 => 86,  260 => 85,  256 => 82,  248 => 78,  244 => 76,  239 => 75,  233 => 72,  229 => 70,  224 => 69,  219 => 66,  206 => 64,  202 => 63,  197 => 61,  193 => 59,  188 => 58,  182 => 55,  178 => 53,  173 => 52,  169 => 50,  164 => 48,  159 => 46,  153 => 44,  149 => 42,  136 => 40,  132 => 39,  129 => 38,  127 => 37,  123 => 35,  116 => 31,  103 => 29,  99 => 28,  94 => 25,  86 => 21,  82 => 19,  74 => 14,  68 => 12,  65 => 11,  59 => 7,  53 => 5,  51 => 4,  47 => 3,  43 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/events/editor_form.twig", "/chroot/home/a2ea4401/73e3dceaec.nxcli.io/html/wp-content/plugins/wp-phpmyadmin-extension/lib/phpMyAdmin_c6QqLe1Hhzg4GUtOuD7d0nX/templates/database/events/editor_form.twig");
    }
}
