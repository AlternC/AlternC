<!DOCTYPE style-sheet PUBLIC "-//James Clark//DTD DSSSL Style Sheet//EN" [
<!ENTITY docbook.dsl PUBLIC "-//Norman Walsh//DOCUMENT DocBook HTML Stylesheet//EN" CDATA DSSSL>
]>

<style-sheet>
<style-specification use="docbook">
<style-specification-body>

(define %stylesheet-version%
  "DocBook HTML Frames Stylesheet version 2.0")


(define %stylesheet% "docbook.css");



(root
 (make sequence
   (process-children)
   (with-mode manifest
     (process-children))
   (make-dhtml-toc)))



(define (make-dhtml-toc)
  (make entity
    system-id: "toc.htm"
    (make element gi: "HTML"
          (make element gi: "HEAD"
                (make element gi: "TITLE" (literal "DocBook TOC"))
                ($standard-html-header$))
          (make element gi: "BODY"
                (with-mode dhtmltoc
                           (process-children))
                )))
  )







(define (dhtml-toc-entry nd gilist)
  (let*

      (
       (subdivnodes (node-list-filter-by-gi (children nd) gilist))
       (subdivs (and (> (node-list-length subdivnodes) 0) (not (node-list=? nd (sgml-root-element))) ) )

       )


    (if (node-list=? nd (sgml-root-element))
                                        ; SEQUENCE 1 : ROOT element
        (make sequence
              (make element gi: "NOBR"            
	      (make element gi: "A"
                    attributes: (list (list "HREF" (href-to (current-node)))
                                      (list "TARGET" "right")
                                      )
                    (element-title-sosofo (current-node))
                    )
              (make empty-element gi: "BR")
              (make element gi: "UL"
		    attributes: (list (list "CLASS" "TOC"))
                    (process-children))
              )
	      )
                                        ; SEQUENCE 2 : Children elements
      (make sequence
            (make empty-element gi: "LI")
            (make element gi: "NOBR"            
            (make element gi: "A"
                  attributes: (list (list "HREF" (href-to (current-node)))
                                    (list "TARGET" "right")
                                    )
                  (element-title-sosofo (current-node))
                  ))
                                        ; DIV ENFANT :
            (make element gi: "UL"
		  attributes: (list (list "CLASS" "TOC2"))
                  (process-children))

            ) ; sequence
      ) ; if node-list ...

    ))










(mode dhtmltoc
      (default (empty-sosofo))

      (element set (dhtml-toc-entry (current-node)
                                    (list (normalize "book"))))

      (element book (dhtml-toc-entry (current-node)
                                     (list (normalize "part")
                                           (normalize "preface")
                                           (normalize "chapter")
                                           (normalize "appendix")
                                           (normalize "reference"))))

      (element preface (dhtml-toc-entry (current-node)
                                        (list (normalize "sect1"))))

      (element part (dhtml-toc-entry (current-node)
                                     (list (normalize "preface")
                                           (normalize "chapter")
                                           (normalize "appendix")
                                           (normalize "reference"))))

      (element chapter (dhtml-toc-entry (current-node)
                                        (list (normalize "sect1"))))

      (element appendix (dhtml-toc-entry (current-node)
                                         (list (normalize "sect1"))))

      (element sect1 (dhtml-toc-entry (current-node) '()))

      (element reference (dhtml-toc-entry (current-node)
                                          (list (normalize "refentry"))))

      (element refentry (dhtml-toc-entry (current-node) '()))

      )



</style-specification-body>
</style-specification>

<external-specification id="docbook" document="docbook.dsl">

</style-sheet>
