<?php
namespace common\models\webdb20;
/**
 * webdb20 に登録不可能だった情報：認定者 一覧
 * 
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/Jphma.php $
 * $Id: Jphma.php 1345 2015-08-22 13:59:24Z mori $
 */
class Jphma
{
    public static function isFamilyHomoeopath($customerid)
    {
        return in_array($customerid, self::getFamilyHomoeopaths());
    }

    public static function isInnerChildTherapist($customerid)
    {
        return in_array($customerid, self::getInnerChildTherapists());
    }

    public static function getFamilyHomoeopaths()
    {
        return [
            1186, 13219, 28123, 35389, 41167, 42745, 50227, 51207, 56873, 58857, 60175, 63132, 64607, 64926, 69523, 76642, 77188, 80535, 81018, 82803, 91627, 95113, 96927, 98288, 108095, 108414, 110713, 113992, 114638, 117290, 118284, 119241, 120160, 120568, 120866, 122638, 123674, 124509, 124876, 127663, 128410, 129309, 129845, 130588, 134354, 134447, 137421, 137558, 138125, 138229, 139395, 139964, 140784, 142057, 146572, 147465, 149065, 149074, 158570, 162605, 164986, 165701, 169439, 170536, 170822, 172874, 174428, 175088, 175958, 176090, 179087, 179430, 179619, 179697, 179821, 179879, 179955, 181040, 181398, 183285, 183772, 183821, 184911, 186149, 186332, 187512, 187824, 188446, 188653, 188755, 190678, 192373, 192411, 192744, 194176
        ];
    }

    public static function getInnerChildTherapists()
    {
        return [
            872, 1396, 1936, 2165, 2207, 6200, 9828, 11355, 11984, 13201, 14450, 18839, 19551, 22073, 23002, 23362, 23819, 26072, 26351, 27571, 30278, 32113, 32649, 34773, 35587, 37724, 41186, 41368, 48909, 49248, 51639, 53823, 54526, 55410, 58857, 60175, 60276, 63132, 63698, 66471, 66623, 66628, 67267, 67877, 70389, 75700, 75837, 76117, 76711, 81515, 82201, 82230, 84587, 90042, 93236, 95047, 95165, 96196, 98308, 98544, 99406, 100103, 100189, 106676, 115017, 115796, 116572, 116605, 118007, 119762, 123514, 125285, 127633, 127663, 128265, 129302, 133368, 134195, 136080, 137652, 139395, 143375, 143970, 144203, 148234, 149065, 149734, 150434, 154891, 157451, 157474, 158111, 159216, 161572, 161624, 163384, 163592, 164215, 164986, 165515, 167193, 170450, 171638, 171846, 175829, 176090, 178170, 180557, 181794, 182352, 183762, 184415, 184747, 185673, 185821, 186119, 186147, 187303, 187426, 187512, 187826, 187850, 187971, 188653, 188705, 188731, 189367, 189945, 190006, 190126, 190177, 190191, 190541, 190612, 190830, 190879, 190917, 192457
        ];
    }

}