(function() {
  "use strict";
  try {
    if (typeof document != "undefined") {
      var elementStyle = document.createElement("style");
      elementStyle.appendChild(document.createTextNode("#body-user .badge-small[data-v-a4bfe739] {\n  display: flex;\n  flex: 0 0 fit-content;\n  align-items: center;\n  gap: 5px;\n  border: 2px solid;\n  border-radius: var(--border-radius-pill) !important;\n  text-align: center;\n  font-size: 0.9em;\n  overflow: hidden;\n  padding: 0px 8px !important;\n  margin: 0 !important;\n  min-height: 1.4rem;\n}\n#body-user .badge-small span[data-v-a4bfe739] {\n  overflow: hidden;\n  text-overflow: ellipsis;\n  white-space: nowrap;\n}\nh2 #body-user .badge-small[data-v-a4bfe739] {\n  font-size: 0.6em;\n}\n#body-user .badge-small.error[data-v-a4bfe739] {\n  background-color: rgba(var(--color-error-rgb), 0.2);\n  border-color: var(--color-error);\n}\n#body-user .badge-small.success[data-v-a4bfe739] {\n  background-color: rgba(var(--color-success-rgb), 0.2);\n  border-color: var(--color-success);\n}\n#body-user .badge-small.warning[data-v-a4bfe739] {\n  background-color: rgba(var(--color-warning-rgb), 0.2) !important;\n  border-color: var(--color-warning);\n}.agora_widget[data-v-dd2984c0] {\n  padding: 0.6rem;\n}\n.widget_header[data-v-dd2984c0],\n.widget_footer[data-v-dd2984c0] {\n  display: flex;\n  column-gap: 0.3rem;\n}\n.badge-small[data-v-dd2984c0] {\n  flex: 0;\n}\n.agora_app_icon[data-v-dd2984c0] {\n  flex: 0 0 1.4rem;\n}\n.title[data-v-dd2984c0] {\n  flex: 1;\n  font-weight: bold;\n  padding-inline-start: 0.6rem;\n  text-wrap: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n}\n.description[data-v-dd2984c0] {\n  margin-inline-start: 1.4rem;\n  padding: 0.6rem;\n}\n.owner[data-v-dd2984c0] {\n  margin-inline-start: 1.4rem;\n  padding-inline-start: 0.6rem;\n}\n.clamped[data-v-dd2984c0] {\n  display: -webkit-box !important;\n  -webkit-line-clamp: 4;\n  line-clamp: 4;\n  -webkit-box-orient: vertical;\n  text-wrap: wrap;\n  overflow: clip !important;\n  text-overflow: ellipsis !important;\n  padding: 0 !important;\n}/*!\n * SPDX-FileCopyrightText: 2025 Trappe Vincent \n * SPDX-License-Identifier: AGPL-3.0-or-later\n */\n.icon-agora {\n  background-image: url(../img/agora.svg);\n  filter: var(--background-invert-if-dark);\n}\n\n.icon-agora-dark {\n  background-image: url(../img/agora-dark.svg);\n  filter: var(--background-invert-if-dark);\n}"));
      document.head.appendChild(elementStyle);
    }
  } catch (e) {
    console.error("vite-plugin-css-injected-by-js", e);
  }
})();
const appName = "agora";
const appVersion = "1.5.1";
import { u as _export_sfc, d as defineComponent, E as mergeModels, F as useModel, l as computed, I as warn, R as RouterLink, J as watch, e as createBlock, o as openBlock, w as withCtx, r as renderSlot, s as resolveDynamicComponent, K as mergeProps, f as createVNode, a as createBaseVNode, c as createElementBlock, C as createCommentVNode, B as unref, L as NcAvatar, M as normalizeStyle, k as toDisplayString, O as NcPopover, _ as _export_sfc$1, P as DateTime, t as translate, j as createTextVNode, A as normalizeClass, h as createApp, p as pinia } from "./ThumbIcon.vue_vue_type_style_index_0_scoped_24ed4f43_lang-B3ZZmmu2.chunk.mjs";
import { r as registerWidget } from "./NcRichText-CETsOVBU-KnJlB91U.chunk.mjs";
import { A as AgoraAppIcon } from "./AgoraAppIcon-DT9qpUxh.chunk.mjs";

const _sfc_main$1$1 = {};
function _sfc_render$3(_ctx, _cache) {
  return openBlock(), createElementBlock("div", null, [
    renderSlot(_ctx.$slots, "trigger")
  ]);
}
const NcUserBubbleDiv = /* @__PURE__ */ _export_sfc(_sfc_main$1$1, [["render", _sfc_render$3]]);
const _hoisted_1$3 = { class: "user-bubble__name" };
const _hoisted_2$2 = {
  key: 0,
  class: "user-bubble__secondary"
};
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "NcUserBubble",
  props: /* @__PURE__ */ mergeModels({
    avatarImage: { default: void 0 },
    user: { default: void 0 },
    displayName: { default: void 0 },
    showUserStatus: { type: Boolean },
    url: { default: void 0 },
    to: { default: void 0 },
    primary: { type: Boolean },
    size: { default: 20 },
    margin: { default: 2 }
  }, {
    "open": { type: Boolean },
    "openModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["click"], ["update:open"]),
  setup(__props, { emit: __emit }) {
    const isOpen = useModel(__props, "open");
    const props = __props;
    const emit = __emit;
    const isAvatarUrl = computed(() => {
      if (!props.avatarImage) {
        return false;
      }
      try {
        const url = new URL(props.avatarImage);
        return !!url;
      } catch {
        return false;
      }
    });
    const isCustomAvatar = computed(() => !!props.avatarImage);
    const avatarStyle = computed(() => ({
      marginInlineStart: `${props.margin}px`
    }));
    const hasUrl = computed(() => {
      if (!props.url || props.url.trim() === "") {
        return false;
      }
      try {
        const url = new URL(props.url, props.url?.startsWith?.("/") ? window.location.href : void 0);
        return !!url;
      } catch {
        warn("[NcUserBubble] Invalid URL passed", { url: props.url });
        return false;
      }
    });
    const href = computed(() => hasUrl.value ? props.url : void 0);
    const contentComponent = computed(() => {
      if (hasUrl.value) {
        return "a";
      } else if (props.to) {
        return RouterLink;
      } else {
        return "div";
      }
    });
    const contentStyle = computed(() => ({
      height: `${props.size}px`,
      lineHeight: `${props.size}px`,
      borderRadius: `${props.size / 2}px`
    }));
    watch([() => props.displayName, () => props.user], () => {
      if (!props.displayName && !props.user) {
        warn("[NcUserBubble] At least `displayName` or `user` property should be set.");
      }
    });
    return (_ctx, _cache) => {
      return openBlock(), createBlock(resolveDynamicComponent(!!_ctx.$slots.default ? unref(NcPopover) : NcUserBubbleDiv), {
        shown: isOpen.value,
        "onUpdate:shown": _cache[1] || (_cache[1] = ($event) => isOpen.value = $event),
        class: "user-bubble__wrapper",
        trigger: "hover focus"
      }, {
        trigger: withCtx(({ attrs }) => [
          (openBlock(), createBlock(resolveDynamicComponent(contentComponent.value), mergeProps({
            class: ["user-bubble__content", { "user-bubble__content--primary": _ctx.primary }],
            style: contentStyle.value,
            to: _ctx.to,
            href: href.value
          }, attrs, {
            onClick: _cache[0] || (_cache[0] = ($event) => emit("click", $event))
          }), {
            default: withCtx(() => [
              createVNode(unref(NcAvatar), {
                url: isCustomAvatar.value && isAvatarUrl.value ? _ctx.avatarImage : void 0,
                "icon-class": isCustomAvatar.value && !isAvatarUrl.value ? _ctx.avatarImage : void 0,
                user: _ctx.user,
                "display-name": _ctx.displayName,
                size: _ctx.size - _ctx.margin * 2,
                style: normalizeStyle(avatarStyle.value),
                "disable-tooltip": true,
                "disable-menu": true,
                "hide-status": !_ctx.showUserStatus,
                class: "user-bubble__avatar"
              }, null, 8, ["url", "icon-class", "user", "display-name", "size", "style", "hide-status"]),
              createBaseVNode("span", _hoisted_1$3, toDisplayString(_ctx.displayName || _ctx.user), 1),
              !!_ctx.$slots.name ? (openBlock(), createElementBlock("span", _hoisted_2$2, [
                renderSlot(_ctx.$slots, "name", {}, void 0, true)
              ])) : createCommentVNode("", true)
            ]),
            _: 2
          }, 1040, ["class", "style", "to", "href"]))
        ]),
        default: withCtx(() => [
          renderSlot(_ctx.$slots, "default", {}, void 0, true)
        ]),
        _: 3
      }, 40, ["shown"]);
    };
  }
});
const NcUserBubble = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-37bde6b7"]]);
const _sfc_main$2 = {
  name: "CalendarEndIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$2 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M22 14V22H20V18L16 22V19H11V17H16V14L20 18V14H22M5 19L9 19V21L5 21C3.9 21 3 20.1 3 19V5C3 3.89 3.9 3 5 3H6V.998H8V3H16V.998H18V3H19C20.11 3 21 3.89 21 5L21 12H19V8H5V19Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon calendar-end-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$1, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$1,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$2);
}
const ExpirationIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/var/www/nextcloud/apps/agora/node_modules/vue-material-design-icons/CalendarEnd.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "BadgeSmallDiv",
  props: {
    tag: { type: String, required: false, default: "span" }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const __returned__ = {};
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock(resolveDynamicComponent($props.tag), { class: "badge-small" }, {
    default: withCtx(() => [
      renderSlot(_ctx.$slots, "icon", {}, void 0, true),
      _ctx.$slots.default ? (openBlock(), createElementBlock("span", _hoisted_1$1, [
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ])) : createCommentVNode("v-if", true)
    ]),
    _: 3
    /* FORWARDED */
  });
}
const BadgeSmallDiv = /* @__PURE__ */ _export_sfc$1(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-a4bfe739"], ["__file", "/var/www/nextcloud/apps/agora/src/components/Base/modules/BadgeSmallDiv.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Reference",
  props: {
    richObject: { type: Object, required: false }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const expiryClass = __props.richObject?.inquiry?.expiry ? DateTime.fromMillis(__props.richObject.inquiry.expiry * 1e3).diffNow("hours").hours < 36 ? "warning" : "success" : "";
    const __returned__ = { expiryClass, get NcUserBubble() {
      return NcUserBubble;
    }, get AgoraAppIcon() {
      return AgoraAppIcon;
    }, ExpirationIcon, BadgeSmallDiv, get t() {
      return translate;
    }, get DateTime() {
      return DateTime;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = {
  key: 0,
  class: "agora_widget"
};
const _hoisted_2 = { class: "widget_header" };
const _hoisted_3 = ["href"];
const _hoisted_4 = { class: "description" };
const _hoisted_5 = { class: "clamped" };
const _hoisted_6 = {
  key: 0,
  class: "widget_footer"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return $props.richObject ? (openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createVNode($setup["AgoraAppIcon"], {
        size: 20,
        class: "title-icon"
      }),
      createBaseVNode("a", {
        class: "title",
        href: $props.richObject.inquiry.url,
        target: "_blank"
      }, toDisplayString($props.richObject.inquiry.title), 9, _hoisted_3),
      $props.richObject.inquiry.participated ? (openBlock(), createBlock($setup["BadgeSmallDiv"], {
        key: 0,
        class: "success"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("agora", "participated")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : $props.richObject.inquiry.expired ? (openBlock(), createBlock($setup["BadgeSmallDiv"], {
        key: 1,
        class: "error"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("agora", "closed")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : $props.richObject.inquiry.expiry > 0 ? (openBlock(), createBlock($setup["BadgeSmallDiv"], {
        key: 2,
        class: normalizeClass($setup.expiryClass)
      }, {
        icon: withCtx(() => [
          createVNode($setup["ExpirationIcon"], { size: 16 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.DateTime.fromMillis($props.richObject.inquiry.expiry * 1e3).toRelative()),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["class"])) : createCommentVNode("v-if", true)
    ]),
    createBaseVNode("div", _hoisted_4, [
      createBaseVNode(
        "span",
        _hoisted_5,
        toDisplayString($props.richObject.inquiry.description),
        1
        /* TEXT */
      )
    ]),
    $props.richObject.inquiry.ownerId ? (openBlock(), createElementBlock("div", _hoisted_6, [
      createBaseVNode(
        "span",
        null,
        toDisplayString($setup.t("agora", "By")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcUserBubble"], {
        user: $props.richObject.inquiry.ownerId,
        "display-name": $props.richObject.inquiry.ownerDisplayName
      }, null, 8, ["user", "display-name"])
    ])) : createCommentVNode("v-if", true)
  ])) : createCommentVNode("v-if", true);
}
const Reference = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-dd2984c0"], ["__file", "/var/www/nextcloud/apps/agora/src/views/Reference.vue"]]);
registerWidget(
  "agora_reference_widget",
  async (el, { richObject }) => {
    const AgoraReference = createApp(Reference, {
      richObject
    }).use(pinia).mount(el);
    return AgoraReference;
  },
  (el) => el.classList.add("nc-agora-reference-widget"),
  {}
);
//# sourceMappingURL=agora-reference.mjs.map
