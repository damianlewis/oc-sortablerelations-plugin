var s, SortableListWidget = {
  settings: function () {
    return {
      relationController: document.querySelector('[id*="RelationController"]')
    }
  },

  init: function () {
    if (document.querySelector('[id*="RelationController"]')) {
      s = this.settings()
      this.factory()

      $(document).on('render', function () {
        SortableListWidget.factory()
      })
    }
  },

  factory: function () {
    this.bindSortable()
    this.createSortable()
    this.bindObserver()
  },

  bindSortable: function () {
    s.relationController.querySelector('[data-control="listwidget"] table tbody')
      .classList
      .add('list-widget-sortable')
  },

  createSortable: function () {
    Sortable.create(s.relationController.querySelector('.list-widget-sortable'), {
      ghostClass: 'list-widget-sortable-ghost',
      onUpdate: function (event) {
        var parent = event.item
          .querySelector('input[id*="Lists-relationViewList-parent-id"]')
        var related = event.item
          .querySelector('input[id*="Lists-relationViewList-related-id"]')

        $('.list-widget-sortable').request('onRelationReorder', {
          data: {
            parentId: parent ? parent.value : null,
            relatedId: related ? related.value : null,
            position: event.newIndex + 1
          }
        })
      }
    })
  },

  bindObserver: function () {
    var callback = (function (mutationsList) {
      for (var mutation in mutationsList) {
        if (mutation.type === 'childList') {
          this.bindSortable()
        }
      }
    }).bind(this)

    var observer = new MutationObserver(callback)

    observer.observe(s.relationController.querySelector('.relation-manager'), {
      childList: true
    })
  }
}

window.onload = function () {
  SortableListWidget.init()
}