
Opener = ($, $field) ->
  
  self = this

  this.$field          = $field
  this.$opener         = $field.find('.opener-button')
  this.$button         = $field.find('.opener-button a')
  this.$copy           = $field.find('.opener-button .copy')
  this.$text           = $field.find('.opener-button a span')
  this.$download       = $field.find('.opener-button a.opener-download')

  this.useDownloadLink = this.$opener.hasClass 'download'
  this.delay           = parseInt(this.$opener.attr('data-delay')) #ms
  this.jsoncode        = String(this.$opener.attr('data-jsoncode'))
  this.jsonmessage     = String(this.$opener.attr('data-jsonmessage'))
  this.jsonfileurl     = String(this.$opener.attr('data-jsonfileurl'))
  
  this.l =
    default : this.$text.attr 'data-textdefault'
    progress: this.$text.attr 'data-textprogress'
    success : this.$text.attr 'data-textsuccess'
    error   : this.$text.attr 'data-texterror'

  this.parseResult = (result) ->
    if result[this.jsoncode] is 200
      self.hasSuccess result[this.jsonmessage], result[this.jsonfileurl]
    else
      self.hasError result.error, result[this.jsonmessage]
    return

  this.hasError = (error, msg) ->
    if msg == undefined then msg = self.l.error
    if msg == undefined or msg.length == 0 then msg = error

    self.$button.addClass('btn-negative')
      .children('span').text msg
    setTimeout ->
      self.$button.removeClass('btn-negative')
      self.$button.children('span').text self.l.default
      self.$download.attr 'href', ''
      self.$download.attr 'download', ''
    , self.delay
    return

  this.hasSuccess = (msg, durl) ->
    if msg == undefined then msg = self.l.success

    self.$button.addClass('btn-positive')
    self.$button.children('span').text msg

    if durl != undefined and durl.trim().length > 0
      if self.useDownloadLink
        dfile = durl.split('/').pop()
        self.$download.attr 'href', durl
        self.$download.attr 'download', dfile
        self.$download[0].click() # http://stackoverflow.com/questions/20928915/jquery-triggerclick-not-working
      else
        window.open durl
    
    setTimeout  ->
      self.$button.removeClass('btn-positive')
      self.$button.children('span').text self.l.default
      self.$download.attr 'href', ''
      self.$download.attr 'download', ''
    , self.delay

    return

  this.init = ->
    return

    # http://stackoverflow.com/questions/22581345/click-button-copy-to-clipboard-using-jquery#22581382
  this.copyToClipboard = (elem) ->
    # create hidden text element, if it doesn't already exist
    targetId = '_hiddenCopyText_'
    isInput = elem.tagName == 'INPUT' or elem.tagName == 'TEXTAREA'
    origSelectionStart = undefined
    origSelectionEnd = undefined
    if isInput
      # can just use the original source element for the selection and copy
      target = elem
      origSelectionStart = elem.selectionStart
      origSelectionEnd = elem.selectionEnd
    else
      # must use a temporary form element for the selection and copy
      target = document.getElementById(targetId)
      if !target
        target = document.createElement('textarea')
        target.style.position = 'absolute'
        target.style.left = '-9999px'
        target.style.top = '0'
        target.id = targetId
        document.body.appendChild target
      target.textContent = elem.textContent
    # select the content
    currentFocus = document.activeElement
    target.focus()
    target.setSelectionRange 0, target.value.length
    # copy the selection
    succeed = undefined
    try
      succeed = document.execCommand('copy')
    catch e
      succeed = false
    # restore original focus
    if currentFocus and typeof currentFocus.focus == 'function'
      currentFocus.focus()
    if isInput
      # restore prior selection
      elem.setSelectionRange origSelectionStart, origSelectionEnd
    else
      # clear temporary content
      target.textContent = ''
    return succeed

  # register to click event
  # on click open url using ajax
  this.$field.find('a.opener').click (ev) ->
    ev.preventDefault()

    if self.$opener.hasClass 'no-ajax'
      window.open self.$button.attr('href')
    else if self.$opener.hasClass 'copy-clipboard'
      self.$copy.removeClass 'jquery-hide'
      if self.copyToClipboard(self.$copy.get(0)) # jquery -> dom
        self.hasSuccess()
      else
        self.hasError()
        window.open self.$button.attr('href')
      self.$copy.addClass 'jquery-hide'
    else
      fname = $(this).attr('name')
      $.fn.OpenerAjax self, $(this).attr('href') + '/panel:1'

    return

  return this.init()

#######################################@@
# jQuery
(($) ->
  $.fn.OpenerAjax = (opener, url) ->
    if opener.$field == undefined
      return

    if opener.$field.hasClass 'ajax'
      return

    opener.$field.addClass 'ajax'
    document.body.style.cursor = 'wait'
    opener.$button.children('span').text opener.l.progress

    $.ajax
      url: url
      type: 'GET'
      success: (result) ->
        opener.parseResult result

      error: (jqXHR, textStatus, errorThrown) -> 
        opener.hasError textStatus + errorThrown

      complete: ->
        opener.$field.removeClass 'ajax'
        document.body.style.cursor = 'default'

    return # fn.ajax

  #######################################
  # Hook into panel initialization.
  $.fn.opener = -> # NOTE: lower- or uppercase __does__ matter for kirby!
    return new Opener($, this)

) jQuery
