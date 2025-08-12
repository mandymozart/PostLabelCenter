export default async function (me) {
    const pluginConfig = await getPluginConfigValue()
    const disableAutomaticLabel = pluginConfig["PostLabelCenter.config.disableAutomaticLabel"]

    if (disableAutomaticLabel !== undefined && disableAutomaticLabel === true) {
        return;
    }

    me.createNotificationInfo({
        message: me.$tc('plc.order.postLabels.modal.creatingLabel'),
    });

    const syncService = Shopware.Service('syncService');
    const httpClient = syncService.httpClient;

    httpClient.post(
        '/plc/create-shipment',
        {
            "orderId": me.order.id,
            "salesChannelId": me.order.salesChannelId
        },
        {
            headers: syncService.getBasicHeaders()
        },
    ).then((response) => {
        if (response.status === 200) {
            if (response.data.data === true) {
                me.createNotificationSuccess({
                    message: me.$tc('plc.order.postLabels.modal.createLabelSuccess')
                });
            } else {
                me.createNotificationError({
                    message: me.$tc('plc.order.postLabels.modal.errorCreatingLabel')
                })

                if (response.data.message !== null) {
                    me.createNotificationError({
                        message: response.data.message
                    })
                }
            }
        } else {
            me.createNotificationError({
                message: me.$tc('plc.order.postLabels.modal.errorCreatingLabel')
            })

            if (response.data.message !== null) {
                me.createNotificationError({
                    message: response.data.message
                })
            }
        }
    })
}

function getPluginConfigValue() {
    const systemConfigApiService = Shopware.Service('systemConfigApiService');
    return systemConfigApiService.getValues('PostLabelCenter');
}