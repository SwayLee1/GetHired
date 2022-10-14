--  tutorial_id INT NOT NULL AUTO_INCREMENT,
--    tutorial_title VARCHAR(100) NOT NULL,
--    tutorial_author VARCHAR(40) NOT NULL,
--    submission_date DATE,
--    PRIMARY KEY ( tutorial_id )

CREATE table tblusers
(
    tblusersid  INT NOT NULL AUTO_INCREMENT, 
    createdon datetime NOW(),
    userhashkey VARCHAR(200) NOT NULL,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( tblusersid )

)
create table tblprofilepic
(
    profilepicid INT NOT NULL AUTO_INCREMENT, 
    profilepicPath  VARCHAR(100) NOT NULL,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( profilepicid )
)


create table tblpersonalinfo
(
    tblpersonalinfoid INT NOT NULL AUTO_INCREMENT,
    city vARCHAR(200) NOT NULL,
    province vARCHAR(200) NOT NULL,
    country vARCHAR(200) NOT NULL,
    mobilenumber vARCHAR(200) NOT NULL,
    whatsappnumber vARCHAR(200) NOT NULL,
    tblusersid int not null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( tblpersonalinfoid )
)


create table tblprefemploymenttype(
    prefemploymenttypeid  INT NOT NULL AUTO_INCREMENT,
    prefemploymenttypetype int not null,
    currentsalary vARCHAR(200) not null, 
    prefsalary  vARCHAR(200) not null, 
    tblusersid int not null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( prefemploymenttypeid )
    )

create table  tblpreflocation
(
   preflocationid INT NOT NULL AUTO_INCREMENT,  
    suburbs  VARCHAR(200) NOT NULL,
    city  VARCHAR(200) NOT NULL,
    tblusersid int not null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( preflocationid )
)

create table tblidealnextjob
(
    idealnextjobid  INT NOT NULL AUTO_INCREMENT,  
    description  VARCHAR(200) NOT NULL,
    tblusersid int not null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( preflocationid )

)


create table tblworkhistory
(
    workhistoryid int not null AUTO_INCREMENT, 
    category varchar(220) NULL,
    title varchar(220) NULL,
    desciption varchar(220) null,
    image_  varchar(220) null,
    FromDate date null,
    ToDate  date null,
    participantid int not null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( workhistoryid )
)


create table tbleducation
(
    educationid int not null AUTO_INCREMENT, 
    description varchar(220) NULL,
    participantid int not null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( educationid )
)


create table tbltopskills
(
    topskillsid int not null AUTO_INCREMENT,
    description varchar(220) NULL,
    preferredroles varchar(220) NULL,
    preferredrolesid int null,
    participantid  int null,
    createdon datetime default NOW(),
    updatedon datetime default null,
    createdby VARCHAR(200) NOT NULL,
    deletedate datetime default null,
    PRIMARY KEY ( topskillsid )

)

create table tblaboutyou
(
aboutyouid int not null AUTO_INCREMENT,
aboutyoudesciption varchar(220) NULL,
participantid  int null,
createdon datetime default NOW(),
updatedon datetime default null,
createdby VARCHAR(200) NOT NULL,
deletedate datetime default null,
PRIMARY KEY ( aboutyouid )
)



--- HOLD --- EXECUTE LATER -- stopped here
online porfolios
--
- participantid
- participantidlinks
createdon
updatedon
createdby
deletedat



projects
projectsid
--galleryid
documents
- participantid
createdon
updatedon
createdby
deletedat

uploads
--uploadsid
--uploadid
- participantid
createdon
updatedon
createdby
deletedat


CV builders online designer
