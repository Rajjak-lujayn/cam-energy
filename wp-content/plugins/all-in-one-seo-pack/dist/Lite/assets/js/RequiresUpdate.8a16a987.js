import{f as a}from"./links.6fea55de.js";import{a as n}from"./addons.393743a4.js";import{R as c,a as m}from"./RequiresUpdate.14823634.js";const p={methods:{getExcludedActivationTabs(r){if(!a().isUnlicensed&&n.isActive(r)&&!n.requiresUpgrade(r))return[];const t=[];return this.$router.options.routes.forEach(e=>{if(!e.meta||!e.meta.middleware)return;(Array.isArray(e.meta.middleware)?e.meta.middleware:[e.meta.middleware]).some(s=>s===c)&&t.push(e.name)}),t}}},f={methods:{getExcludedUpdateTabs(r){if(!a().isUnlicensed&&n.hasMinimumVersion(r)&&!n.requiresUpgrade(r))return[];const t=[];return this.$router.options.routes.forEach(e=>{if(!e.meta||!e.meta.middleware)return;(Array.isArray(e.meta.middleware)?e.meta.middleware:[e.meta.middleware]).some(s=>s===m)&&t.push(e.name)}),t}}};export{p as R,f as a};
