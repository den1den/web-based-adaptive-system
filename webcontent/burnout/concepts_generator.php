<?php
include_once 'util.php';
ob_start();
?>
$options {
    default.properties "event;strict"
    default.order "auto"
}

// root concept of the entire thesis
_concept {
    strict 'true'
    // count user clicks of suitable concepts :)
    #[visited]:Integer
    event +'
        boolean countclick = ${#suitability};
    '

    ///  Test variable
    #[burnout]:Integer
    event +''

    // flag to indicate that new content is available
    #[new-content]:Boolean 'false'

    // Resources are .xml files with the same name as the concept. Unsuitable resources go
    // to unsuitable.xml first. "skip-unsuitable" is a request parameter that allows to
    // skip unsuitable.xml.
    #[new-session]:Boolean
    event +'
        String externalUrl = gale.req().getParameter("external");
        boolean external = (externalUrl != null && !"".equals(externalUrl.trim()));
        ${#new-session} = !"true".equals(gale.req().getParameter("open-session")) && !"".equals(${_concept->(personal)#last-session}) && !${_concept->(personal)#last-session}.equals(gale.req().getSession().getId());
        if (gale.req().getParameter("tags") != null)
            countclick = false;
        if ((external && ${#suitability}) || ${[[=_personal]]#first-time})
            countclick = false;
        if (${[[=_personal]]#review} && ${#new-session})
            countclick = false;
        if (${#unknown})
            countclick = false;
    '
    #resource ='~
        if (${[[=_personal]]#first-time})
            return "[[=intro.xhtml]]";
        return "[[=layout.xhtml]]";
    '

    // knowledge definitions
    /// BUGGY CODE!
    #[own-knowledge]:Double
    event + 'if (countclick) ${#own-knowledge} = 1; else if (${#own-knowledge} < 0.3) ${#own-knowledge} = 0.3;'
    #knowledge:Double ='avg(new Object[] {${<=(parent)#knowledge}, ${#own-knowledge}})'
    #known-all:Boolean ='${#knowledge} > 0.8'
    #known:Boolean ='${#own-knowledge} > 0.8'

    // prerequisites
    #available:Boolean ='${#known} || and(new Object[] {${=>(prereq)#known}, ${=>(prereq-all)#known-all}})'
    #suitability:Boolean ='and(new Object[] {${=>(prereq)#available}, ${=>(prereq-all)#known-all}}) && (!${_concept->(personal)#beginner} || ${#beginner})'

    // tags shown based on whether you view the concept for the first time
    #tags ='~
        if (${#visited} > 3) return "default";
        if ("true".equals(${?intro-first}))
            return (${#visited} > 1?"default":"intro");
        return "intro;default";
    '
    #tags.class '~
        if (!gale.isObject() && "long".equals(element.attributeValue("tag")))
            return "th-sidenote";
        return null;
    '

    // layout related stuff
    #layout.css ='~
        StringBuilder sb = new StringBuilder("[[=burnout.css]]");
        if (!${[[=_personal]]#first-time}) {
            if (!${[[=_personal]]#menu})
                sb.insert(0, "[[=nomenu.css]];");
            else
                sb.insert(0, "[[=menu.css]];");
            if (${[[=_personal]]#use-comments})
                sb.insert(0, "[[=comments.css]];");
            if (${[[=_personal]]#review})
                sb.insert(0, "[[=review.css]];");
        }
        sb.append(";http://fonts.googleapis.com/css?family=Carme");
        return sb.toString();
    '
    #layout.title '"Burnout - "+${.}.getTitle()'
    #link.hide '!${#hierarchy}'
    #link.classexpr '~
        return "neutral";
    '
    
    
    #link.iconlist 'null'
    #unknown:Boolean ="false"
    #[beginner]:Boolean ='!"false".equals(${?beginner})'
    #hierarchy:Boolean ='(${_concept->(personal)#menu} || (${#suitability} && !${#unknown})) && ${->(parent)#suitability}'

    // used to mark a concept that was too advanced but understood anyway
    #[mark]:Integer {
        event '
            Concept[] list = ${->(prereq)};
            for (int i=0;i < list.length;i++) {
                URI uri = session.resolve(list[i].getUri()+"#own-knowledge");
                EntityValue ev = (EntityValue)session.get(uri);
                session.put(uri, new EntityValue(uri, 1d));
            }
            list = ${->(prereq-all)};
            for (int i=0;i < list.length;i++) {
                URI uri = session.resolve(list[i].getUri()+"#mark-all");
                EntityValue ev = (EntityValue)session.get(uri);
                session.put(uri, new EntityValue(uri, ((Integer)ev.getValue())+1));
            }
        '
    }
    #[mark-all]:Integer {
        event '
            ${#own-knowledge} = 1d;
            Concept[] list = ${<-(parent)};
            for (int i=0;i < list.length;i++) {
                URI uri = session.resolve(list[i].getUri()+"#mark-all");
                EntityValue ev = (EntityValue)session.get(uri);
                session.put(uri, new EntityValue(uri, ((Integer)ev.getValue())+1));
            }
        '
    }

    event +'
        if (countclick) {${#visited}++; ${#new-content} = false;}
    '
    ->(personal)_personal

    // enable a reviewer to return to the previous concept when he begins a new session
    event +'
        if (${_concept->(personal)#review} && (countclick || "true".equals(gale.req().getParameter("open-session")))) {
            ${_concept->(personal)#last-concept} = ${.}.getUriString();
            ${_concept->(personal)#last-session} = gale.req().getSession().getId();
        }
    '

}

// root concept defining variables that change depending on how often they are viewed
_variable {
    // count the number of views
    #[count]:Integer
    event +"${#count}++;"
    // define the resource as based on the number of views and the #value string
    #resource:String "content:#content"
    #content:String ='pick(${#count}, ${#value})'
    // a semicolon seperated list of strings that represents the values (overwritten in
    // children)
    #value:String ='"variable (" + ${.}.getUri() + ")"'
}

// personal settings for the thesis
_personal {
    // menu on the left or as drop down
    #[menu]:Boolean 'true'
    // first time to visit the thesis
    #[first-time]:Boolean 'true'
    // role
    #[role] 'intermediate' {
        event '
            if ("beginner".equals(changed.newValue)) {
                ${#beginner} = true;
            }
            if ("intermediate".equals(changed.newValue)) {
                ${#beginner} = false;
            }
            if ("expert".equals(changed.newValue)) {
                ${application#mark-all}++;
                ${#analytic-count} = 512;
                ${#beginner} = false;
            }
        '
    }
    #[review]:Boolean 'false'
    #[beginner]:Boolean 'false'
    #[beginner-level]:Integer '0'
    #[use-comments]:Boolean 'false'
    #[last-concept]
    #[last-session]
    #[analytic-count]:Integer '-2' {
        event 'if ((Integer)changed.newValue == 0 && changed.diff != 0) ${#analytic-count} = changed.diff*2;'
    }
    #analytic:Boolean ='${#analytic-count} > 0'
}

application {->(extends)_concept
    title 'Main Page'
    no-title 'false'
}

introduction {->(extends)_concept
    ->(parent)application
    #available:Boolean ='${#known}'
    title 'Introduction'
}

/// Questions are numbered, the text of a question is in a sperate file
/// The question number devided by 100 is the category
question {->(extends)_concept
    ->(parent)application
    #available:Boolean ='${#known}'
    title 'Question'
    no-title 'true'
    #[previousQuestion]:Integer
    event + '
        try{
            ${#previousQuestion} = Integer.parseInt(gale.req().getParameter("pq"));
        }catch(Exception e){
            System.out.println("GET `pq` not set: " + String.valueOf(e));
            ${#previousQuestion} = -1;
        }
    '
    /// OR COULD BE: event + '~ return Integer.parseInt(gale.req().getParameter("pq"));'

    #[previousAnswer]:Integer
    event + '
    try{
        ${#previousAnswer} = Integer.parseInt(gale.req().getParameter("pa"));
    } catch(Exception e) {
        System.out.println("GET `pa` not set: " + String.valueOf(e));
        ${#previousAnswer} = -1;
    }
    '
    #[userScore]:String
    <?php /*
 event + '
         float[][] questionScoreArray = new float[][]{new float[]{}};
         class Scope {
             static String arr2str(float[][] arr2str_a){
                 StringBuilder arr2str_b = new StringBuilder();
                 for(int i = 0; i < arr2str_a.length; i++){
                 for(int j = 0; j < arr2str_a[i].length; j++){
                 arr2str_b.append(arr2str_a[i][j]);
                 if(j < arr2str_a[i].length - 1) arr2str_b.append("_");
                 }
                 if(i < arr2str_a.length - 1) arr2str_b.append("I");
                 }
             }
         }
     '
    event + '
        final boolean resetUserQuestionScore = false; // Used to set ${#questionScore} to 0 
    
        //questionScoreArray[i][j] = score on category j for question i on positive answer
        float[][] questionScoreArray = new float[][]{
            new float[]{.1f, .1f, .1f, .1f, .1f, .1f, .1f},
            new float[]{.2f, .1f, .1f, .1f, .1f, .1f, .1f},
            new float[]{.3f, .1f, .1f, .1f, .1f, .1f, .1f},
            new float[]{.4f, .1f, .1f, .1f, .1f, .1f, .1f},
            new float[]{.5f, .1f, .1f, .1f, .1f, .1f, .1f}
        };
        int previousQuestion = ${#previousQuestion};
        float previousAnswerImpact = ${#previousAnswer} * 1f; // the impact of the answer (-1, 1)
        
        String questionScoreStr = ${#questionScore};
        if(resetUserQuestionScore || "".equals(questionScoreStr)){
            // set ${#questionScore} to 0 if it was not set
            // <emptyarr2str>
            StringBuilder emptyarr2str = new StringBuilder();
            for(int i = 0; i < questionScoreArray.length; i++){
            for(int j = 0; j < questionScoreArray[i].length; j++){
            emptyarr2str.append(0);
            if(j < questionScoreArray[i].length - 1) emptyarr2str.append("_");
            }
            if(i < questionScoreArray.length - 1) emptyarr2str.append("I");
            }
            // </emptyarr2str>
            questionScoreStr = String.valueOf(emptyarr2str);
        }
        // read ${#questionScore} to an array
        <?php //java_str2arr('questionScoreStr', 'questionScore', 2); ?>
        for(int i = 0; i < questionScoreArray[previousQuestion].length; i++){
            questionScore[previousQuestion][i] += questionScoreArray[previousQuestion][i] * previousAnswerImpact;
        }
        
        // store array as string again
        // <arr2str>
        StringBuilder arr2str = new StringBuilder();
        for(int i = 0; i < questionScore.length; i++){
            for(int j = 0; j < questionScore[i].length; j++){
                arr2str.append(questionScore[i][j]);
                if(j < questionScore[i].length - 1) arr2str.append("_");
            }
            if(i < questionScore.length - 1) arr2str.append("I");
        }
        // </arr2str>
        questionScoreStr = String.valueOf(arr2str);
        ${#questionScore} = String.valueOf(questionScoreStr);
    '

 */ ?>
    event + '
        final boolean resetUserScore = true;
        <?php PHP_PRINT($config['question_values'], 'questionScores'); // 2d ?>

        // Read userScore
        float[] userScore;
        String userScoreStr = ${#userScore};
        if(resetUserScore || "".equals(userScoreStr)){
            <?php
            PHP_PRINT($config['default_user_profile'], 'defaultUserScore');
            JAVA_ENCODE('defaultUserScore', 'defaultUserScoreStr', 1); ?>
            System.out.println("setting defaultUserScoreStr = " + defaultUserScoreStr);
            userScore = defaultUserScore;
        } else {
            <?php JAVA_DECODE('userScoreStr', 'userScore', 1, true); ?>
        }
        <?php // TODO: do something with `float[] userProfile;`; ?>
        <?php JAVA_ENCODE('userScore', 'userScoreStr', 1, true); ?>
        ${#userScore} = userScoreStr;
        <?php // Set questions
        //PHP_PRINT($config['questions'], 'questions', 'String');
        /*
        ${#questionScore} = userProfileStr;
        int previousAnswer = ${#previousAnswer};
        String questionStr = questions[previousAnswer][1];
        System.out.println(questionStr);
        ${_concept->(question)#question_txt} = "questionStr";
        */
        ?>
    '
    <?php /* /// TODO store the result:
    // #scores:String ='" "'
    event + ''
    /// affect values = q[id][c] (2d array of question id and copying ID)

    /// if(${#scores} == null){
    ///     ${#scores} = new float[7]
    /// };
    /// ${#scores}[${#previousQuestion}/100] += 1;

    /// Process previous question:
    /// #[stressors].get()

    ///
    /// answer_1 = 'Of course'
    /// answer_1_link = 'http://www.google.nl/' /// answer id different then answer index!
    /// answer_2 = 'What is a retard?'
    /// answer_2_link = 'http://www.google.nl/'
    /// answer_3 = 'Nope, i'm an airplane'
    /// answer_3_link = 'http://www.google.nl/'
    */ ?>
}
settings {->(extends)_concept
    ->(parent)application
    title 'Settings'
}
<?php
file_put_contents('concepts.gam', ob_get_clean());
?>