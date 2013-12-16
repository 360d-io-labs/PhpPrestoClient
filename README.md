PhpPrestoClient
===============

Simple Php Class to connect to a PrestoDB Server that runs distributed queries against 
a Hadoop HDFS cluster.
Presto uses a subset of SQL as its query language. Presto is an alternative for
Hadoop-Hive.


Usage
-----------------
See Demo.php for a short demo on how to use it

Requirement
-----------------
Php-Curl

Presto client protocol
----------------------
The following description was made by Ivo Herweijer for its Python interface

The communication protocol used between Presto clients and servers is not documented yet. It seems to
be as follows:

Client sends http POST request to the Presto server, page: "/v1/statement". Header information should
include: X-Presto-Catalog, X-Presto-Source, X-Presto-Schema, User-Agent, X-Presto-User. The body of the
request should contain the sql statement. The server responds by returning JSON data (http status-code 200).
This reply may contain up to 3 uri's. One giving the link to get more information about the query execution
('infoUri'), another giving the link to fetch the next packet of data ('nextUri') and one with the uri to
cancel the query ('partialCancelUri').

The client should send GET requests to the server (Header: X-Presto-Source, User-Agent, X-Presto-User.
Body: empty) following the 'nextUri' link from the previous response until the servers response does not
contain an 'nextUri' link anymore. When there is no 'nextUri' the query is finished. If the last response
from the server included an error section ('error') the query failed, otherwise the query succeeded. If
the http status of the server response is anything other than 200 with Content-Type application/json, the
query should also be considered failed. A 503 http response means that the server is (too) busy. Retry the
request after waiting at least 50ms.
The server response may contain a 'state' variable. This is for informational purposes only (may be subject
to change in future implementations).
Each response by the server to a 'nextUri' may contain information about the columns returned by the query
and all- or part of the querydata. If the response contains a data section the columns section will always
be available.

The server reponse may contain a variable with the uri to cancel the query ('partialCancelUri'). The client
may issue a DELETE request to the server using this link. Response http status-code is 204.

The Presto server will retain information about finished queries for 15 minutes. When a client does not
respond to the server (by following the 'nextUri' links) the server will cancel these 'dead' queries after
5 minutes. These timeouts are hardcoded in the Presto server source code.


Thanks
------

Thanks to Ivo Herweijer from easywarehousing.com that is doing a Python interface and from which I copied the 
protocol description and took some inspiration for the Php interface.
