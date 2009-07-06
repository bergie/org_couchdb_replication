CouchDb replication requests
============================

## 1. Look up session document:

    1> [info] [<0.202.0>] 127.0.0.1 - - 'GET' /midgard/_local%2Ff093279c28b90fa19b76cf7e74d6273a 404

## 2. All documents from sequences 0 to 100

    1> [debug] [<0.205.0>] couch_rep HTTP get request: http://localhost:5984/midgard/_all_docs_by_seq?limit=100&startkey=0
    1> [info] [<0.150.0>] starting new replication "f093279c28b90fa19b76cf7e74d6273a" at <0.197.0>
    1> [debug] [<0.204.0>] 'GET' /midgard/_all_docs_by_seq?limit=100&startkey=0 {1,1}
    Headers: [{'Host',"localhost:5984"}]
    1> [info] [<0.204.0>] 127.0.0.1 - - 'GET' /midgard/_all_docs_by_seq?limit=100&startkey=0 200

## 3. Get document "blah"

    1> [debug] [<0.197.0>] couch_rep HTTP get request: http://localhost:5984/midgard/blah?revs=true&latest=true&open_revs=["5-4097526248"]
    1> [debug] [<0.207.0>] 'GET' /midgard/blah?revs=true&latest=true&open_revs=["5-4097526248"] {1,1}
    Headers: [{'Host',"localhost:5984"}]
    1> [info] [<0.207.0>] 127.0.0.1 - - 'GET' /midgard/blah?revs=true&latest=true&open_revs=["5-4097526248"] 200

## 4. All documents from sequences 5 to 100 (same as req 2)

    1> [debug] [<0.205.0>] couch_rep HTTP get request: http://localhost:5984/midgard/_all_docs_by_seq?limit=100&startkey=5
    1> [debug] [<0.209.0>] 'GET' /midgard/_all_docs_by_seq?limit=100&startkey=5 {1,1}
    Headers: [{'Host',"localhost:5984"}]
    1> [info] [<0.209.0>] 127.0.0.1 - - 'GET' /midgard/_all_docs_by_seq?limit=100&startkey=5 200
    1> [info] [<0.197.0>] recording a checkpoint at source update_seq 5