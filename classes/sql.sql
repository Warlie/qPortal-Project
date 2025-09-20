SELECT tag_collection.id, tag_collection.type, tag_collection.content, attrib_collection.name, attrib_collection.value, tag_collection.ref FROM 
(tag_collection LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid ) 
LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id 
WHERE tag_collection.group = 'kontakt' ORDER BY tag_collection.order;
