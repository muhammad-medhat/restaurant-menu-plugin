jQuery(function ($) {
    const list = $('#rm-item-order-list');
    const order = $('#rm-item-order-form input[name="rm_item_order"]');

    if (!list.length) {
        return;
    }

    function updateOrder() {
        const items = list.children('li');
        order.val(items.map(function () {
            return this.dataset.itemId;
        }).get().join(','));
    }

    list.sortable({
        handle: '.rm-item-order-handle',
        placeholder: 'rm-item-order-placeholder',
        forcePlaceholderSize: true,
        update: updateOrder
    });

    list.on('click', '.rm-item-order-up, .rm-item-order-down', function () {
        const item = $(this).closest('li');
        const moveUp = $(this).hasClass('rm-item-order-up');
        const neighbor = moveUp ? item.prev('li') : item.next('li');

        if (!neighbor.length) {
            return;
        }

        if (moveUp) {
            item.insertBefore(neighbor);
        } else {
            item.insertAfter(neighbor);
        }

        this.focus();
        updateOrder();
    });
});
