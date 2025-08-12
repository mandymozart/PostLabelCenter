import deDE from "./snippet/de-DE.json";
import enGB from "./snippet/en-GB.json";
import "./component/daily-statement";
import "./component/address-data";
import "./component/return-reason";
import "./component/bank-data";
import "./component/shipping-service";
import "./component/bulk-label-modal";
import "./component/config-delivery-state";
import "./component/custom-field-entity-select";
import "./component/merged-label-modal";
import "./component/shipping-label-create-modal";
import "./component/post-logo-icon";
import "./extension/plc-create-label-extension";
import "./extension/sw-order-detail";
import "./extension/sw-order-details-state-card";
import "./extension/sw-order-list";
import "./extension/sw-order-general-info";
import "./extension/sw-order-state-history-card";
import "./extension/shipping-document-tab";
import "./extension/sw-settings-shipping-detail";
import "./extension/sw-system-config";
import "./extension/order-return-tab";

const { Module } = Shopware;

Module.register("post-label-center", {
  type: "plugin",
  name: "plc",
  title: "plc.menu.mainMenuItemmenu",
  description: "plc.menu.descriptionTextModule",
  color: "#ff3d58",
  snippets: {
    "de-DE": deDE,
    "en-GB": enGB,
  },

  routeMiddleware(next, currentRoute) {
    const orderDocumentsRoute = "plc.order.documents";
    const returnOrdersRoute = "plc.order.returnData";

    if (
      currentRoute.name === "sw.order.detail" &&
      currentRoute.children.every(
        (currentRoute) =>
          currentRoute.name !== orderDocumentsRoute &&
          currentRoute.name !== returnOrdersRoute
      )
    ) {
      currentRoute.children.push(
        {
          component: "shipping-document-tab",
          name: orderDocumentsRoute,
          meta: {
            parentPath: "sw.order.index",
          },
          path: "/sw/order/detail/:id/shipping-document",
        },
        {
          component: "order-return-tab",
          name: returnOrdersRoute,
          meta: {
            parentPath: "sw.order.index",
          },
          path: "/sw/order/detail/:id/order-return",
        }
      );
    }
    next(currentRoute);
  },
});
