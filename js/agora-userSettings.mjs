const appName = "agora";
const appVersion = "1.5.0-beta";
import { d as defineComponent, t as translate, b as onMounted, _ as _export_sfc, e as createBlock, o as openBlock, w as withCtx, f as createVNode, n as normalizeProps, g as guardReactiveProps, h as createApp, p as pinia } from "./ThumbIcon.vue_vue_type_style_index_0_scoped_24ed4f43_lang-DIwIn7KM.chunk.mjs";
import { N as NcSettingsSection } from "./index-CJF071qK.chunk.mjs";
import "./NcDashboardWidget-BEUtfCxs-l3e9BVoJ.chunk.mjs";
import { F as FlexSettings } from "./FlexSettings-DosVYPvr.chunk.mjs";
import { u as usePreferencesStore, S as StyleSettings, F as FeatureSettings } from "./StyleSettings-CnDE-B96.chunk.mjs";
import "./NcRichText-Dht_wH3t-CVcCbfMN.chunk.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "UserSettingsPage",
  setup(__props, { expose: __expose }) {
    __expose();
    const preferencesStore = usePreferencesStore();
    const sections = {
      personalSettings: {
        name: translate("agora", "Personal preferences"),
        description: translate("agora", "Set your personal preferences for the agora app")
      },
      styleSettings: {
        name: translate("agora", "Experimental styles"),
        description: translate("agora", "Some visual styling options.")
      }
    };
    onMounted(() => {
      preferencesStore.load();
    });
    const __returned__ = { preferencesStore, sections, get NcSettingsSection() {
      return NcSettingsSection;
    }, get FlexSettings() {
      return FlexSettings;
    }, get FeatureSettings() {
      return FeatureSettings;
    }, get StyleSettings() {
      return StyleSettings;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["FlexSettings"], null, {
    default: withCtx(() => [
      createVNode(
        $setup["NcSettingsSection"],
        normalizeProps(guardReactiveProps($setup.sections.personalSettings)),
        {
          default: withCtx(() => [
            createVNode($setup["FeatureSettings"])
          ]),
          _: 1
          /* STABLE */
        },
        16
        /* FULL_PROPS */
      ),
      createVNode(
        $setup["NcSettingsSection"],
        normalizeProps(guardReactiveProps($setup.sections.styleSettings)),
        {
          default: withCtx(() => [
            createVNode($setup["StyleSettings"])
          ]),
          _: 1
          /* STABLE */
        },
        16
        /* FULL_PROPS */
      )
    ]),
    _: 1
    /* STABLE */
  });
}
const UserSettingsPage = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/var/www/nextcloud/apps/agora/src/views/UserSettingsPage.vue"]]);
const Agora = createApp(UserSettingsPage).use(pinia);
Agora.mount("#content_agora");
//# sourceMappingURL=agora-userSettings.mjs.map
